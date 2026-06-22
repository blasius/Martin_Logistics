<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;

class CurrencyService
{
    public function convert(float $amount, Currency $from, Currency $to, ?Carbon $date = null): float
    {
        if ($from->id === $to->id) {
            return $amount;
        }

        $date = $date ?? now();

        $rate = ExchangeRate::where('base_currency_id', $from->id)
            ->where('target_currency_id', $to->id)
            ->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->orderByDesc('valid_from')
            ->first();

        if (!$rate) {
            $rate = ExchangeRate::where('base_currency_id', $to->id)
                ->where('target_currency_id', $from->id)
                ->where('valid_from', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
                })
                ->orderByDesc('valid_from')
                ->first();

            if ($rate) {
                return $amount / (float) $rate->rate;
            }

            return $amount;
        }

        return $amount * (float) $rate->rate;
    }

    public function format(float $amount, Currency $from, Currency $to, ?Carbon $date = null): string
    {
        $converted = $this->convert($amount, $from, $to, $date);
        $formatted = number_format($converted, $to->code === 'RWF' ? 0 : 2);

        if ($from->id !== $to->id) {
            $original = number_format($amount, $from->code === 'RWF' ? 0 : 2);
            return "{$to->symbol} {$formatted} (≈ {$original} {$from->code})";
        }

        return "{$to->symbol} {$formatted}";
    }

    public function formatDefault(float $amount, Currency $from, ?Carbon $date = null): string
    {
        $default = Currency::where('is_default', true)->first();
        if (!$default) {
            return number_format($amount, 0) . ' ' . $from->code;
        }
        return $this->format($amount, $from, $default, $date);
    }

    public function getRate(Currency $from, Currency $to, ?Carbon $date = null): ?ExchangeRate
    {
        $date = $date ?? now();

        $rate = ExchangeRate::where('base_currency_id', $from->id)
            ->where('target_currency_id', $to->id)
            ->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->orderByDesc('valid_from')
            ->first();

        if (!$rate && $from->id !== $to->id) {
            $inverse = ExchangeRate::where('base_currency_id', $to->id)
                ->where('target_currency_id', $from->id)
                ->where('valid_from', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
                })
                ->orderByDesc('valid_from')
                ->first();

            if ($inverse) {
                $rate = new ExchangeRate();
                $rate->rate = 1 / (float) $inverse->rate;
                $rate->valid_from = $inverse->valid_from;
                $rate->valid_to = $inverse->valid_to;
            }
        }

        return $rate;
    }
}
