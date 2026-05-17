<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function download(Request $request): StreamedResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            abort(403, 'Admin only');
        }

        $cfg  = config('database.connections.' . config('database.default'));
        $host = $cfg['host']     ?? '127.0.0.1';
        $port = $cfg['port']     ?? 3306;
        $db   = $cfg['database'] ?? '';
        $user_ = $cfg['username'] ?? 'root';
        $pass = $cfg['password'] ?? '';

        $filename = $db . '_backup_' . now()->format('Y-m-d_His') . '.sql';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Build command — pass password via env var to avoid shell exposure
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --routines --triggers --hex-blob %s',
            escapeshellarg($host),
            (int) $port,
            escapeshellarg($user_),
            escapeshellarg($db)
        );

        $env = array_merge($_ENV, ['MYSQL_PWD' => $pass]);

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (!is_resource($process)) {
            abort(500, 'mysqldump could not be started. Ensure mysqldump is installed and in PATH.');
        }

        fclose($pipes[0]);

        return response()->stream(function () use ($process, $pipes) {
            while (!feof($pipes[1])) {
                echo fread($pipes[1], 65536);
                ob_flush();
                flush();
            }
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);

            if ($stderr && !str_contains($stderr, 'Warning')) {
                // Non-warning stderr means something went wrong — already streamed partial output, log it
                logger()->error('mysqldump stderr: ' . $stderr);
            }
        }, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Accel-Buffering'   => 'no',
        ]);
    }
}
