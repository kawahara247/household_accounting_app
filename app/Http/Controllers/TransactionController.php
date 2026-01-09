<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayerType;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'category_id' => $request->input('category_id'),
            'payer'       => $request->input('payer'),
            'type'        => $request->input('type'),
            'memo'        => $request->input('memo'),
        ];

        $transactions = Transaction::with('category')
            ->filter($filters)
            ->orderBy('date', 'desc')
            ->get();

        $summary = [
            'income'  => $transactions->where('type', 'income')->sum('amount'),
            'expense' => $transactions->where('type', 'expense')->sum('amount'),
        ];

        $categories = Category::all();

        $payers = collect(PayerType::cases())->map(fn (PayerType $payer) => [
            'value' => $payer->value,
            'label' => $payer->label(),
        ]);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories'   => $categories,
            'payers'       => $payers,
            'filters'      => $filters,
            'summary'      => $summary,
        ]);
    }

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        Transaction::create($request->validated());

        $redirect = $request->input('_redirect', 'transactions.index');

        return Redirect::route($redirect);
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction): RedirectResponse
    {
        $transaction->update($request->validated());

        return Redirect::route('transactions.index');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return Redirect::route('transactions.index');
    }
}
