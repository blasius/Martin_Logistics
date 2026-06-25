<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletSettlement;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    public function index(Request $request)
    {
        $query = Wallet::with(['user:id,name,email', 'currency:id,code,symbol']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('balance_min')) {
            $query->where('current_balance', '<=', $request->balance_min);
        }

        return $query->orderByDesc('current_balance')->paginate($request->per_page ?? 20);
    }

    public function show(Wallet $wallet)
    {
        $wallet->load(['user:id,name,email', 'currency:id,code,symbol']);
        return $wallet;
    }

    public function myWallet(Request $request)
    {
        $user = Auth::user();
        $currencyId = $request->currency_id ?? Currency::where('is_default', true)->value('id');

        $wallet = $this->walletService->getOrCreate($user->id, $currencyId);
        $wallet->load('currency:id,code,symbol');

        return $wallet;
    }

    public function myTransactions(Request $request)
    {
        $user = Auth::user();
        $currencyId = $request->currency_id ?? Currency::where('is_default', true)->value('id');

        $transactions = $this->walletService->getTransactions($user->id, $currencyId, $request->only(['category', 'type', 'from', 'to', 'per_page']));

        return $transactions;
    }

    public function transactions(Request $request, Wallet $wallet)
    {
        $query = $wallet->transactions()->with('source', 'creator:id,name');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $query->orderByDesc('created_at')->paginate($request->per_page ?? 15);
    }

    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $currencyId = $validated['currency_id'] ?? Currency::where('is_default', true)->value('id');
        $wallet = $this->walletService->getOrCreate($validated['user_id'], $currencyId);

        $txn = $validated['type'] === 'credit'
            ? $this->walletService->credit(
                $wallet,
                $validated['amount'],
                $validated['category'],
                $validated['description'] ?? null,
                null,
                Auth::id()
            )
            : $this->walletService->debit(
                $wallet,
                $validated['amount'],
                $validated['category'],
                $validated['description'] ?? null,
                null,
                Auth::id()
            );

        return response()->json($txn->load('creator:id,name'), 201);
    }

    public function acknowledge(WalletTransaction $transaction)
    {
        if ($transaction->user_acknowledged_at) {
            return response()->json(['message' => 'Already acknowledged'], 422);
        }

        $transaction->update(['user_acknowledged_at' => now()]);

        return $transaction;
    }

    public function settle(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'method' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $settlement = WalletSettlement::create([
            'wallet_id' => $wallet->id,
            'period_start' => $wallet->last_settled_at?->toDateString() ?? $wallet->created_at->toDateString(),
            'period_end' => now()->toDateString(),
            'balance_at_settlement' => $wallet->current_balance,
            'amount_settled' => abs((float) $wallet->current_balance),
            'method' => $validated['method'],
            'settled_at' => now(),
            'settled_by' => Auth::id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $wallet->update(['current_balance' => 0, 'last_settled_at' => now()]);

        return response()->json($settlement->load('settler:id,name'), 201);
    }

    public function settlements(Wallet $wallet)
    {
        return $wallet->settlements()->with('settler:id,name')->orderByDesc('created_at')->get();
    }
}
