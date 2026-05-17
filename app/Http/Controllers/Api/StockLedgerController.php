<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class StockLedgerController extends Controller
{
    public function index()
    {
        $productId = request('product_id');
        $fromDate  = request('from_date');
        $toDate    = request('to_date');

        if (!$productId) {
            return response()->json(['movements' => [], 'product' => null, 'current_stock' => 0]);
        }

        $product = Product::with(['partCategory:id,name', 'brand:id,name'])->find($productId);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        // Stock IN — from received purchases
        $ins = PurchaseItem::with(['purchase:id,purchase_number,purchased_at,supplier_id', 'purchase.supplier:id,name'])
            ->where('product_id', $productId)
            ->whereHas('purchase', fn($q) => $q->whereIn('status', ['received', 'partial']))
            ->when($fromDate, fn($q) => $q->whereHas('purchase', fn($q2) => $q2->whereDate('purchased_at', '>=', $fromDate)))
            ->when($toDate,   fn($q) => $q->whereHas('purchase', fn($q2) => $q2->whereDate('purchased_at', '<=', $toDate)))
            ->get()
            ->map(fn($item) => [
                'date'        => $item->purchase->purchased_at,
                'reference'   => $item->purchase->purchase_number,
                'type'        => 'IN',
                'description' => 'Purchase — ' . ($item->purchase->supplier->name ?? ''),
                'qty_in'      => $item->received_quantity ?? $item->quantity,
                'qty_out'     => 0,
                'unit_cost'   => $item->unit_cost,
            ]);

        // Stock OUT — from sales
        $outs = SaleItem::with(['sale:id,invoice_number,sold_at'])
            ->where('product_id', $productId)
            ->when($fromDate, fn($q) => $q->whereHas('sale', fn($q2) => $q2->whereDate('sold_at', '>=', $fromDate)))
            ->when($toDate,   fn($q) => $q->whereHas('sale', fn($q2) => $q2->whereDate('sold_at', '<=', $toDate)))
            ->get()
            ->map(fn($item) => [
                'date'        => $item->sale->sold_at,
                'reference'   => $item->sale->invoice_number,
                'type'        => 'OUT',
                'description' => 'Sale — ' . $item->sale->invoice_number,
                'qty_in'      => 0,
                'qty_out'     => $item->quantity,
                'unit_cost'   => $item->unit_price,
            ]);

        // Stock RETURN IN — from purchase returns
        $returnIns = \App\Models\PurchaseReturnItem::with(['purchaseReturn:id,return_number,return_date,supplier_id', 'purchaseReturn.supplier:id,name'])
            ->where('product_id', $productId)
            ->when($fromDate, fn($q) => $q->whereHas('purchaseReturn', fn($q2) => $q2->whereDate('return_date', '>=', $fromDate)))
            ->when($toDate,   fn($q) => $q->whereHas('purchaseReturn', fn($q2) => $q2->whereDate('return_date', '<=', $toDate)))
            ->get()
            ->map(fn($item) => [
                'date'        => $item->purchaseReturn->return_date,
                'reference'   => $item->purchaseReturn->return_number,
                'type'        => 'RETURN',
                'description' => 'Return to ' . ($item->purchaseReturn->supplier->name ?? ''),
                'qty_in'      => 0,
                'qty_out'     => $item->quantity,
                'unit_cost'   => $item->unit_cost,
            ]);

        $movements = $ins->concat($outs)->concat($returnIns)
            ->sortBy('date')
            ->values();

        // Add running balance (working backwards from current stock)
        $balance = $product->stock_quantity;
        $withBalance = collect();
        foreach ($movements->reverse() as $row) {
            $withBalance->prepend(array_merge($row, ['balance' => $balance]));
            $balance -= $row['qty_in'];
            $balance += $row['qty_out'];
        }

        return response()->json([
            'product'       => $product,
            'current_stock' => $product->stock_quantity,
            'movements'     => $withBalance->values(),
        ]);
    }

    public function products()
    {
        $user = request()->user();
        $products = Product::select('id', 'name', 'sku', 'stock_quantity', 'part_category_id')
            ->with('partCategory:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%"))
            ->orderBy('name')
            ->limit(200)
            ->get();
        return response()->json($products);
    }
}
