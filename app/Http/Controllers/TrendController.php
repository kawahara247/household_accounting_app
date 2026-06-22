<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\TrendAggregator;
use App\Services\TrendQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrendController extends Controller
{
    public function __construct(
        private readonly TrendAggregator $aggregator,
        private readonly TrendQuery $query,
    ) {
    }

    public function index(Request $request): Response
    {
        $typeStr      = $request->input('type', FlowType::Expense->value);
        $isBalance    = $typeStr === 'balance';
        $endMonth     = $request->input('end_month', now()->format('Y-m'));
        $startMonth   = $request->input('start_month', now()->subMonths(11)->format('Y-m'));
        $splitByPayer = $request->boolean('split_by_payer', false);

        $labels    = $this->aggregator->generateMonthRange($startMonth, $endMonth);
        $startDate = Carbon::parse($startMonth . '-01')->startOfMonth();
        $endDate   = Carbon::parse($endMonth . '-01')->endOfMonth();

        if ($isBalance) {
            if ($splitByPayer) {
                $rows     = $this->query->fetchPayerSplitBalanceMonthlyTotals($startDate, $endDate);
                $datasets = $this->aggregator->buildPayerSplitBalanceDatasets($labels, $rows);
            } else {
                $rows     = $this->query->fetchBalanceMonthlyTotals($startDate, $endDate);
                $datasets = $this->aggregator->buildMergedBalanceDatasets($labels, $rows);
            }
        } else {
            $type       = FlowType::from($typeStr);
            $categories = Category::where('type', $type)->orderBy('id')->get();
            if ($splitByPayer) {
                $rows     = $this->query->fetchPayerSplitCategoryMonthlyTotals($type, $startDate, $endDate);
                $datasets = $this->aggregator->buildPayerSplitDatasets($labels, $categories, $rows);
            } else {
                $rows     = $this->query->fetchCategoryMonthlyTotals($type, $startDate, $endDate);
                $datasets = $this->aggregator->buildMergedDatasets($labels, $categories, $rows);
            }
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
}
