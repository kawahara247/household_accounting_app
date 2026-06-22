<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FlowType;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use stdClass;

class TrendQuery
{
    /**
     * カテゴリ×月の合計（payer合算）
     *
     * 各要素の動的プロパティ: year_month(string), category_id(int), total(int)
     *
     * @return Collection<int, stdClass>
     */
    public function fetchCategoryMonthlyTotals(FlowType $type, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::selectRaw("strftime('%Y-%m', date) as year_month, category_id, SUM(amount) as total")
            ->where('type', $type)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month', 'category_id')
            ->toBase()
            ->get();
    }

    /**
     * カテゴリ×月×payer の合計
     *
     * 各要素の動的プロパティ: year_month(string), category_id(int), payer(string), total(int)
     *
     * @return Collection<int, stdClass>
     */
    public function fetchPayerSplitCategoryMonthlyTotals(FlowType $type, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::selectRaw("strftime('%Y-%m', date) as year_month, category_id, payer, SUM(amount) as total")
            ->where('type', $type)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month', 'category_id', 'payer')
            ->toBase()
            ->get();
    }

    /**
     * 月別の収入合計・支出合計（payer合算）
     *
     * 各要素の動的プロパティ: year_month(string), income_total(int), expense_total(int)
     *
     * @return Collection<int, stdClass>
     */
    public function fetchBalanceMonthlyTotals(Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::selectRaw(
            "strftime('%Y-%m', date) as year_month, " .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, ' .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
            [FlowType::Income->value, FlowType::Expense->value]
        )
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month')
            ->toBase()
            ->get();
    }

    /**
     * 月別×payer別の収入合計・支出合計
     *
     * 各要素の動的プロパティ: year_month(string), payer(string), income_total(int), expense_total(int)
     *
     * @return Collection<int, stdClass>
     */
    public function fetchPayerSplitBalanceMonthlyTotals(Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::selectRaw(
            "strftime('%Y-%m', date) as year_month, payer, " .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, ' .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
            [FlowType::Income->value, FlowType::Expense->value]
        )
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month', 'payer')
            ->toBase()
            ->get();
    }
}
