<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getOrCreate(int $userId, ?int $currencyId = null): Wallet
    {
        $currencyId = $currencyId ?? Currency::where('is_default', true)->value('id');
        return Wallet::firstOrCreate(
            ['user_id' => $userId, 'currency_id' => $currencyId],
            ['current_balance' => 0]
        );
    }

    public function credit(
        Wallet $wallet,
        float $amount,
        string $category,
        ?string $description = null,
        ?Model $source = null,
        ?int $createdBy = null,
        bool $userVisible = true,
    ): WalletTransaction {
        return $this->createTransaction($wallet, 'credit', $amount, $category, $description, $source, $createdBy, $userVisible);
    }

    public function debit(
        Wallet $wallet,
        float $amount,
        string $category,
        ?string $description = null,
        ?Model $source = null,
        ?int $createdBy = null,
        bool $userVisible = true,
    ): WalletTransaction {
        return $this->createTransaction($wallet, 'debit', $amount, $category, $description, $source, $createdBy, $userVisible);
    }

    protected function createTransaction(
        Wallet $wallet,
        string $type,
        float $amount,
        string $category,
        ?string $description,
        ?Model $source,
        ?int $createdBy,
        bool $userVisible,
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $type, $amount, $category, $description, $source, $createdBy, $userVisible) {
            $balanceBefore = (float) $wallet->current_balance;
            $balanceAfter = $type === 'credit'
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            $txn = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency_id' => $wallet->currency_id,
                'category' => $category,
                'description' => $description,
                'source_type' => $source ? get_class($source) : null,
                'source_id' => $source ? $source->getKey() : null,
                'created_by' => $createdBy ?? Auth::id(),
                'user_visible' => $userVisible,
            ]);

            $wallet->update(['current_balance' => $balanceAfter]);

            return $txn;
        });
    }

    public function getBalance(int $userId, ?int $currencyId = null): float
    {
        $wallet = Wallet::where('user_id', $userId)
            ->where('currency_id', $currencyId ?? Currency::where('is_default', true)->value('id'))
            ->first();

        return $wallet ? (float) $wallet->current_balance : 0;
    }

    public function getTransactions(int $userId, ?int $currencyId = null, array $filters = [])
    {
        $wallet = Wallet::where('user_id', $userId)
            ->where('currency_id', $currencyId ?? Currency::where('is_default', true)->value('id'))
            ->first();

        if (!$wallet) {
            return collect();
        }

        $query = $wallet->transactions()->with('source', 'creator:id,name');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')->paginate($filters['per_page'] ?? 15);
    }
}
