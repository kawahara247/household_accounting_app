<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PayerType;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use stdClass;

class TrendAggregator
{
    /**
     * 開始月〜終了月の YYYY-MM ラベル配列を生成する.
     *
     * @return array<int, string>
     */
    public function generateMonthRange(string $startMonth, string $endMonth): array
    {
        $months  = [];
        $current = Carbon::parse($startMonth . '-01');
        $end     = Carbon::parse($endMonth . '-01');

        while ($current->lte($end)) {
            $months[] = $current->format('Y-m');
            $current->addMonth();
        }

        return $months;
    }

    /**
     * カテゴリ別月別合計データセット（payer合算）.
     *
     * @param array<int, string> $labels
     * @param Collection<int, Category> $categories
     * @param Collection<int, stdClass> $rows year_month, category_id, total
     *
     * @return array<int, array{name: string, data: array<int, int>}>
     */
    public function buildMergedDatasets(
        array $labels,
        Collection $categories,
        Collection $rows,
    ): array {
        $grouped = $rows->groupBy('category_id');

        return $categories->map(function (Category $category) use ($labels, $grouped): array {
            $monthTotals = collect($grouped->get($category->id, []))->pluck('total', 'year_month');

            return [
                'name' => $category->name,
                'data' => collect($labels)->map(
                    fn (string $month): int => (int) ($monthTotals->get($month) ?? 0)
                )->all(),
            ];
        })->all();
    }

    /**
     * カテゴリ × payer の月別合計データセット.
     *
     * @param array<int, string> $labels
     * @param Collection<int, Category> $categories
     * @param Collection<int, stdClass> $rows year_month, category_id, payer, total
     *
     * @return array<int, array{name: string, payer: string, payerLabel: string, data: array<int, int>}>
     */
    public function buildPayerSplitDatasets(
        array $labels,
        Collection $categories,
        Collection $rows,
    ): array {
        $grouped = $rows->groupBy(fn (stdClass $row): string => $row->category_id . ':' . $row->payer);

        $datasets = [];

        foreach ($categories as $category) {
            foreach (PayerType::cases() as $payer) {
                $key         = $category->id . ':' . $payer->value;
                $monthTotals = collect($grouped->get($key, []))->pluck('total', 'year_month');

                $datasets[] = [
                    'name'       => $category->name,
                    'payer'      => $payer->value,
                    'payerLabel' => $payer->label(),
                    'data'       => collect($labels)->map(
                        fn (string $month): int => (int) ($monthTotals->get($month) ?? 0)
                    )->all(),
                ];
            }
        }

        return $datasets;
    }

    /**
     * 月別収支データセット（payer合算）.
     *
     * @param array<int, string> $labels
     * @param Collection<int, stdClass> $rows year_month, income_total, expense_total
     *
     * @return array<int, array{name: string, data: array<int, int>}>
     */
    public function buildMergedBalanceDatasets(
        array $labels,
        Collection $rows,
    ): array {
        $keyed = $rows->keyBy('year_month');

        $data = collect($labels)->map(
            fn (string $month): int => $this->balanceOf($keyed->get($month))
        )->all();

        return [['name' => '収支', 'data' => $data]];
    }

    /**
     * payer 別の月別収支データセット.
     *
     * @param array<int, string> $labels
     * @param Collection<int, stdClass> $rows year_month, payer, income_total, expense_total
     *
     * @return array<int, array{name: string, payer: string, payerLabel: string, data: array<int, int>}>
     */
    public function buildPayerSplitBalanceDatasets(
        array $labels,
        Collection $rows,
    ): array {
        $grouped = $rows->groupBy('payer');

        $datasets = [];

        foreach (PayerType::cases() as $payer) {
            $monthTotals = collect($grouped->get($payer->value, []))->keyBy('year_month');

            $datasets[] = [
                'name'       => '収支',
                'payer'      => $payer->value,
                'payerLabel' => $payer->label(),
                'data'       => collect($labels)->map(
                    fn (string $month): int => $this->balanceOf($monthTotals->get($month))
                )->all(),
            ];
        }

        return $datasets;
    }

    private function balanceOf(?stdClass $row): int
    {
        if ($row === null) {
            return 0;
        }

        return (int) $row->income_total - (int) $row->expense_total;
    }
}
