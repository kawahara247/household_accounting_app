<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayerType;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(): Response
    {
        $transactions = Transaction::with('category')
            ->orderBy('date', 'desc')
            ->get();

        $categories = Category::all();

        $payers = collect(PayerType::cases())->map(fn (PayerType $payer) => [
            'value' => $payer->value,
            'label' => $payer->label(),
        ]);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories'   => $categories,
            'payers'       => $payers,
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
