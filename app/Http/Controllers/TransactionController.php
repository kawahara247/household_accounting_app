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
        // デフォルトで現在の年月を使用
        $defaultYearMonth = now()->format('Y-m');

        $categoryId = $request->input('category_id');

        $filters = [
            'category_id' => $categoryId !== null ? (int) $categoryId : null,
            'payer'       => $request->input('payer'),
            'type'        => $request->input('type'),
            'memo'        => $request->input('memo'),
            // year_month がURLに明示的に含まれる場合はその値を使用
            // （空文字はConvertEmptyStringsToNullミドルウェアによりnullになる = 全件表示）
            // 含まれない場合（初回アクセス・リセット後）は現在年月をデフォルト使用
            'year_month'  => $request->exists('year_month')
                ? $request->input('year_month')
                : $defaultYearMonth,
        ];

        // 取引が存在する年月を降順で取得
        $availableYearMonths = Transaction::selectRaw("strftime('%Y-%m', date) as year_month")
            ->distinct()
            ->orderByRaw('year_month DESC')
            ->pluck('year_month')
            ->values()
            ->toArray();

        // 現在年月がリストにない場合は先頭に追加
        if (! in_array($defaultYearMonth, $availableYearMonths, true)) {
            array_unshift($availableYearMonths, $defaultYearMonth);
        }

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
            'yearMonths'   => $availableYearMonths,
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

        $redirect = $request->input('_redirect', 'transactions.index');

        return Redirect::route($redirect);
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        $redirect = $request->input('_redirect', 'transactions.index');

        return Redirect::route($redirect);
    }
}
