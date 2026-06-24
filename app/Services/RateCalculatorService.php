<?php

namespace App\Services;

use App\Models\RateCard;
use App\Models\RateCardItem;
use App\Models\RateCalculatorRequest;

class RateCalculatorService
{
    public function calculate(RateCalculatorRequest $request): array
    {
        $rateCard = $this->resolveRateCard($request);

        if (!$rateCard) {
            return [
                'line_items' => [],
                'subtotal' => 0,
                'total' => 0,
                'currency_id' => null,
                'rate_card_id' => null,
                'notes' => 'No applicable rate card found',
            ];
        }

        $items = $rateCard->items()->with('currency')->get();
        $lineItems = [];
        $subtotal = 0;
        $currencyId = null;

        foreach ($items as $item) {
            $charge = $this->computeCharge($item, $request);
            $lineItems[] = [
                'charge_name' => $item->charge_name,
                'charge_type' => $item->charge_type,
                'amount' => $item->amount,
                'calculated' => $charge,
                'currency_id' => $item->currency_id,
            ];
            $subtotal += $charge;
            $currencyId = $currencyId ?? $item->currency_id;
        }

        return [
            'line_items' => $lineItems,
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'currency_id' => $currencyId,
            'rate_card_id' => $rateCard->id,
            'rate_card_name' => $rateCard->name,
            'notes' => null,
        ];
    }

    public function resolveRateCard(RateCalculatorRequest $request): ?RateCard
    {
        if ($request->rateCardId) {
            return RateCard::with('items')->where('id', $request->rateCardId)
                ->where('is_active', true)->first();
        }

        $query = RateCard::with('items')
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now());
            })
            ->orderBy('effective_from', 'desc');

        if ($request->clientId) {
            $query->where(function ($q) use ($request) {
                $q->where('client_id', $request->clientId)->orWhereNull('client_id');
            });
        } else {
            $query->whereNull('client_id');
        }

        return $query->first();
    }

    protected function computeCharge(RateCardItem $item, RateCalculatorRequest $request): float
    {
        $base = match ($item->charge_type) {
            'per_km' => $item->amount * ($request->distanceKm ?? 0),
            'per_kg' => $item->amount * (($request->weightKg ?? 0) / 1000),
            'per_container' => $item->amount * ($request->containerCount ?? 1),
            'flat' => $item->amount,
            'per_stop' => $item->amount * ($request->stopCount ?? 1),
            'fuel_surcharge' => $this->computeFuelSurcharge($item, $request),
            default => 0,
        };

        if ($item->min_charge !== null && $base < $item->min_charge) {
            $base = $item->min_charge;
        }

        if ($item->max_charge !== null && $base > $item->max_charge) {
            $base = $item->max_charge;
        }

        return round($base, 2);
    }

    protected function computeFuelSurcharge(RateCardItem $item, RateCalculatorRequest $request): float
    {
        $perKmItems = RateCardItem::where('rate_card_id', $item->rate_card_id)
            ->where('charge_type', 'per_km')
            ->sum('amount');

        $distanceCharge = $perKmItems * ($request->distanceKm ?? 0);

        return $distanceCharge * ($item->amount / 100);
    }
}
