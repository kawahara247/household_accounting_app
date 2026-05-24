<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrendController extends Controller
{
    public function index(Request $request): Response
    {
        $typeStr      = $request->input('type', FlowType::Expense->value);
        $isBalance    = $typeStr === 'balance';
        $endMonth     = $request->input('end_month', now()->format('Y-m'));
        $startMonth   = $request->input('start_month', now()->subMonths(11)->format('Y-m'));
        $splitByPayer = $request->boolean('split_by_payer', false);

        $labels    = $this->generateMonthRange($startMonth, $endMonth);
        $startDate = Carbon::parse($startMonth . '-01')->startOfMonth();
        $endDate   = Carbon::parse($endMonth . '-01')->endOfMonth();

        if ($isBalance) {
            $datasets = $splitByPayer
                ? $this->buildPayerSplitBalanceDatasets($startDate, $endDate, $labels)
                : $this->buildMergedBalanceDatasets($startDate, $endDate, $labels);
        } else {
            $type       = FlowType::from($typeStr);
            $categories = Category::where('type', $type)->orderBy('id')->get();
            $datasets   = $splitByPayer
                ? $this->buildPayerSplitDatasets($type, $startDate, $endDate, $labels, $categories)
                : $this->buildMergedDatasets($type, $startDate, $endDate, $labels, $categories);
        }

        $availableMonths = collect(
            Transaction::selectRaw("strftime('%Y-%m', date) as year_month")
                ->distinct()
                ->orderByRaw('year_month DESC')
                ->pluck('year_month')
                ->all()
        )
            ->push($startMonth, $endMonth)
            ->unique()
            ->sort()
            ->reverse()
            ->values()
            ->all();

        $payers = collect(PayerType::cases())->map(fn (PayerType $payer) => [
            'value' => $payer->value,
            'label' => $payer->label(),
        ])->values()->all();

        return Inertia::render('Trends/Index', [
            'type'            => $typeStr,
            'filters'         => [
                'start_month' => $startMonth,
                'end_month'   => $endMonth,
            ],
            'splitByPayer'    => $splitByPayer,
            'labels'          => $labels,
            'datasets'        => $datasets,
            'availableMonths' => $availableMonths,
            'payers'          => $payers,
        ]);
    }

    /**
     * @param array<int, string> $labels
     *
     * @return array<int, array{name: string, data: array<int, int>}>
     */
    private function buildMergedBalanceDatasets(
        Carbon $startDate,
        Carbon $endDate,
        array $labels,
    ): array {
        $rows = Transaction::selectRaw(
            "strftime('%Y-%m', date) as year_month, " .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, ' .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
            [FlowType::Income->value, FlowType::Expense->value]
        )
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month')
            ->toBase()
            ->get()
            ->keyBy('year_month');

        $data = collect($labels)->map(function (string $month) use ($rows): int {
            $row = $rows->get($month);

            return $row !== null
                ? (int) $row->income_total - (int) $row->expense_total
                : 0;
        })->values()->all();

        return [['name' => '収支', 'data' => $data]];
    }

    /**
     * @param array<int, string> $labels
     *
     * @return array<int, array{name: string, payer: string, payerLabel: string, data: array<int, int>}>
     */
    private function buildPayerSplitBalanceDatasets(
        Carbon $startDate,
        Carbon $endDate,
        array $labels,
    ): array {
        $rows = Transaction::selectRaw(
            "strftime('%Y-%m', date) as year_month, payer, " .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, ' .
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
            [FlowType::Income->value, FlowType::Expense->value]
        )
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month', 'payer')
            ->toBase()
            ->get()
            ->groupBy('payer');

        $datasets = [];

        foreach (PayerType::cases() as $payer) {
            $monthTotals = collect($rows->get($payer->value, []))->keyBy('year_month');

            $datasets[] = [
                'name'       => '収支',
                'payer'      => $payer->value,
                'payerLabel' => $payer->label(),
                'data'       => collect($labels)->map(function (string $month) use ($monthTotals): int {
                    $row = $monthTotals->get($month);

                    return $row !== null
                        ? (int) $row->income_total - (int) $row->expense_total
                        : 0;
                })->values()->all(),
            ];
        }

        return $datasets;
    }

    /**
     * @param array<int, string> $labels
     * @param \Illuminate\Support\Collection<int, Category> $categories
     *
     * @return array<int, array{name: string, data: array<int, int>}>
     */
    private function buildMergedDatasets(
        FlowType $type,
        Carbon $startDate,
        Carbon $endDate,
        array $labels,
        \Illuminate\Support\Collection $categories,
    ): array {
        $rows = Transaction::selectRaw("strftime('%Y-%m', date) as year_month, category_id, SUM(amount) as total")
            ->where('type', $type)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month', 'category_id')
            ->get()
            ->groupBy('category_id');

        return $categories->map(function (Category $category) use ($labels, $rows): array {
            $monthTotals = collect($rows->get($category->id, []))->pluck('total', 'year_month');

            return [
                'name' => $category->name,
                'data' => collect($labels)->map(
                    fn (string $month): int => (int) ($monthTotals->get($month) ?? 0)
                )->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @param array<int, string> $labels
     * @param \Illuminate\Support\Collection<int, Category> $categories
     *
     * @return array<int, array{name: string, payer: string, payerLabel: string, data: array<int, int>}>
     */
    private function buildPayerSplitDatasets(
        FlowType $type,
        Carbon $startDate,
        Carbon $endDate,
        array $labels,
        \Illuminate\Support\Collection $categories,
    ): array {
        $rows = Transaction::selectRaw("strftime('%Y-%m', date) as year_month, category_id, payer, SUM(amount) as total")
            ->where('type', $type)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupBy('year_month', 'category_id', 'payer')
            ->get()
            ->groupBy(fn ($row): string => $row->category_id . ':' . $row->payer->value);

        $datasets = [];

        foreach ($categories as $category) {
            foreach (PayerType::cases() as $payer) {
                $key         = $category->id . ':' . $payer->value;
                $monthTotals = collect($rows->get($key, []))->pluck('total', 'year_month');

                $datasets[] = [
                    'name'       => $category->name,
                    'payer'      => $payer->value,
                    'payerLabel' => $payer->label(),
                    'data'       => collect($labels)->map(
                        fn (string $month): int => (int) ($monthTotals->get($month) ?? 0)
                    )->values()->all(),
                ];
            }
        }

        return $datasets;
    }

    /** @return array<int, string> */
    private function generateMonthRange(string $startMonth, string $endMonth): array
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
}
