<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PoItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function create(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items) {
            $data['reference'] = $data['reference'] ?? static::generateReference();
            $data['order_date'] = $data['order_date'] ?? now();
            $data['status'] = 'draft';

            $po = PurchaseOrder::create($data);

            foreach ($items as $item) {
                $item['total'] = $item['total'] ?? ($item['quantity'] * $item['unit_price']);
                $po->items()->create($item);
            }

            $po->update(['total_amount' => $po->items->sum('total')]);

            return $po->fresh(['items', 'vendor']);
        });
    }

    public function send(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => 'sent']);

        return $po->fresh();
    }

    public function confirm(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => 'confirmed']);

        return $po->fresh();
    }

    public function receive(PurchaseOrder $po, array $receivedItems, int $warehouseId, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems, $warehouseId, $userId) {
            foreach ($receivedItems as $itemData) {
                $poItem = PoItem::findOrFail($itemData['po_item_id']);
                $qty = $itemData['quantity'];
                $poItem->increment('received_quantity', $qty);

                $stockLevel = StockLevel::firstOrCreate(
                    ['warehouse_id' => $warehouseId, 'part_id' => $poItem->part_id],
                    ['quantity' => 0, 'min_quantity' => 0]
                );
                $stockLevel->increment('quantity', $qty);

                StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'part_id' => $poItem->part_id,
                    'quantity' => $qty,
                    'type' => 'in',
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $po->id,
                    'user_id' => $userId,
                    'notes' => "Received from PO {$po->reference}",
                ]);
            }

            $allReceived = $po->items->every(fn ($item) => $item->received_quantity >= $item->quantity);
            $anyReceived = $po->items->sum('received_quantity') > 0;

            if ($allReceived) {
                $po->update(['status' => 'received']);
            } elseif ($anyReceived) {
                $po->update(['status' => 'partially_received']);
            }

            return $po->fresh(['items', 'vendor']);
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => 'cancelled']);

        return $po->fresh();
    }

    public static function generateReference(): string
    {
        $prefix = 'PO-';
        $date = now()->format('Ymd');
        $last = PurchaseOrder::where('reference', 'like', "{$prefix}{$date}-%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = $last ? (int) substr($last->reference, -4) + 1 : 1;

        return "{$prefix}{$date}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
