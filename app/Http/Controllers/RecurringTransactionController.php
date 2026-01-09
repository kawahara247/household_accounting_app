<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayerType;
use App\Http\Requests\RecurringTransactionStoreRequest;
use App\Http\Requests\RecurringTransactionUpdateRequest;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function index(): Response
    {
        $recurringTransactions = RecurringTransaction::with('category')
            ->orderBy('day_of_month')
            ->get();

        $categories = Category::all();

        $payers = collect(PayerType::cases())->map(fn (PayerType $payer) => [
            'value' => $payer->value,
            'label' => $payer->label(),
        ]);

        return Inertia::render('RecurringTransactions/Index', [
            'recurringTransactions' => $recurringTransactions,
            'categories'            => $categories,
            'payers'                => $payers,
        ]);
    }

    public function store(RecurringTransactionStoreRequest $request): RedirectResponse
    {
        RecurringTransaction::create($request->validated());

        return Redirect::route('recurring-transactions.index');
    }

    public function update(RecurringTransactionUpdateRequest $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $recurringTransaction->update($request->validated());

        return Redirect::route('recurring-transactions.index');
    }

    public function destroy(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $recurringTransaction->delete();

        return Redirect::route('recurring-transactions.index');
    }
}
