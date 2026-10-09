<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index()
    {
        try {
            $accounts = Auth::user()->accounts()->with(['transactions.category'])->get();
            return response()->json($accounts);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function display()
    {
        $categories = Category::all();
        $accounts = Account::where('user_id', Auth::id())->get();
        return view('Transactions', compact('categories', 'accounts'));
    }

    public function store(Request $request)
    {
        $account = Account::where('id', $request->account_id)->where('user_id', Auth::id())->first();

        if (!$account) {
            return response()->json(['error' => 'Invalid account'], 403);
        }

        $validate = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'date' => 'required|date',
            'type' => 'required|string|in:income,expense',
        ]);

        if ($validate['type'] === 'expense' && $account->balance < $validate['amount']) {
            return response()->json(['message' => 'Insufficient funds for this transaction.'], 422);
        }

        return DB::transaction(function () use ($validate, $request, $account) {
            $validate['user_id'] = Auth::id();
            $transaction = Transaction::create($validate);

            if ($request->type === 'income') {
                $account->increment('balance', $request->amount);
            } else {
                $account->decrement('balance', $request->amount);
            }

            $transaction->load(['category', 'account']);
            return response()->json($transaction, 201);
        });
    }

    public function show($id)
    {
        $transaction = Transaction::with(['category', 'account'])->where('user_id', Auth::id())->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($transaction);
    }

    public function update(Request $request, string $id)
    {
        $transaction = Transaction::where('user_id', Auth::id())->find($id);
        
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $validate = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'date' => 'required|date',
            'type' => 'required|string|in:income,expense',
        ]);

        return DB::transaction(function () use ($transaction, $validate, $request) {
            // 1. Revert amount from the OLD account
            $oldAccount = Account::find($transaction->account_id);
            if ($oldAccount) {
                if ($transaction->type === 'income') {
                    $oldAccount->decrement('balance', $transaction->amount);
                } else {
                    $oldAccount->increment('balance', $transaction->amount);
                }
            }

            // 2. Update the transaction with new data
            $transaction->update($validate);

            // 3. Apply the new amount to the NEW account
            $newAccount = Account::where('id', $request->account_id)->where('user_id', Auth::id())->first();
            if (!$newAccount) {
                throw new \Exception('Invalid account target.');
            }

            if ($request->type === 'income') {
                $newAccount->increment('balance', $request->amount);
            } else {
                $newAccount->decrement('balance', $request->amount);
            }

            $transaction->load(['category', 'account']);
            return response()->json($transaction, 200);
        });
    }

    public function destroy(string $id)
    {
        $transaction = Transaction::where('user_id', Auth::id())->find($id);
        
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return DB::transaction(function () use ($transaction) {
            $account = Account::find($transaction->account_id);
            
            if ($account) {
                if ($transaction->type === 'income') {
                    $account->decrement('balance', $transaction->amount);
                } else {
                    $account->increment('balance', $transaction->amount);
                }
            }

            $transaction->delete();
            return response()->json(['message' => 'Transaction deleted']);
        });
    }
}