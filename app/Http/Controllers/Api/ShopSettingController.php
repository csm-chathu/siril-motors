<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ShopSettingController extends Controller
{
    private const ALLOWED_KEYS = [
        'shop_name', 'address', 'phone', 'br_number', 'logo_url', 'print_mode',
    ];

    public function branding()
    {
        $all = ShopSetting::allAsObject();

        return response()->json([
            'shop_name' => $all['shop_name'] ?? config('app.name', 'Jewellery Store'),
            'logo_url'  => $all['logo_url'] ?? '',
        ]);
    }

    public function index()
    {
        return response()->json(ShopSetting::allAsObject());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shop_name'  => 'nullable|string|max:200',
            'address'    => 'nullable|string|max:500',
            'phone'      => 'nullable|string|max:50',
            'br_number'  => 'nullable|string|max:100',
            'logo_url'   => 'nullable|string|max:1000',
            'print_mode' => 'nullable|in:pos,a5',
        ]);

        foreach (self::ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                ShopSetting::setValue($key, $data[$key]);
            }
        }

        return response()->json(ShopSetting::allAsObject());
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,gif,webp,svg|max:2048',
        ]);

        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        $folder    = config('services.cloudinary.folder', 'shop');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return response()->json(['message' => 'Cloudinary is not configured. Check CLOUDINARY_* env variables.'], 500);
        }

        $timestamp = time();
        $params    = ['folder' => $folder, 'timestamp' => $timestamp];
        ksort($params);
        $signString = urldecode(http_build_query($params)) . $apiSecret;
        $signature  = sha1($signString);

        $file = $request->file('logo');

        $verifySsl = config('services.cloudinary.verify_ssl', true);

        $response = Http::withOptions(['verify' => filter_var($verifySsl, FILTER_VALIDATE_BOOLEAN)])
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder'    => $folder,
        ]);

        if (!$response->successful() || empty($response->json('secure_url'))) {
            return response()->json([
                'message' => 'Cloudinary upload failed: ' . ($response->json('error.message') ?? $response->status()),
            ], 502);
        }

        // Delete old Cloudinary asset if it exists
        $old = ShopSetting::getValue('logo_url');
        if ($old && str_contains($old, 'cloudinary.com')) {
            $this->deleteCloudinaryAsset($old, $cloudName, $apiKey, $apiSecret);
        }

        $url = $response->json('secure_url');
        ShopSetting::setValue('logo_url', $url);

        return response()->json(['logo_url' => $url]);
    }

    private function deleteCloudinaryAsset(string $url, string $cloudName, string $apiKey, string $apiSecret): void
    {
        // Extract public_id from URL: .../upload/v123456/folder/filename.ext
        if (!preg_match('#/upload/(?:v\d+/)?(.+?)(?:\.\w+)?$#', $url, $m)) {
            return;
        }
        $publicId  = $m[1];
        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$apiSecret}");

        $verifySsl = config('services.cloudinary.verify_ssl', true);
        Http::withOptions(['verify' => filter_var($verifySsl, FILTER_VALIDATE_BOOLEAN)])
            ->asForm()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
            'public_id' => $publicId,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
    }
}
