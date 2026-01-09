<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * 指定した年月の取引データを全て取得する.
     *
     * @param int $year 取得対象の年
     * @param int $month 取得対象の月
     *
     * @return Collection<int, Transaction> 該当月の取引コレクション
     */
    public function getMonthlyTransactions(int $year, int $month): Collection
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        return Transaction::whereBetween('date', [$startOfMonth, $endOfMonth])->get();
    }

    /**
     * 指定した日付の取引データをカテゴリ情報付きで取得する.
     *
     * @param Carbon $date 取得対象の日付
     *
     * @return Collection<int, Transaction> 該当日の取引コレクション（カテゴリリレーション含む）
     */
    public function getTransactionsByDate(Carbon $date): Collection
    {
        return Transaction::with('category')
            ->whereDate('date', $date)
            ->get();
    }

    /**
     * 取引データを日ごとにグループ化し、各日の収支を計算する.
     *
     * @param Collection<int, Transaction> $transactions 月間の取引コレクション
     *
     * @return array<int, array{income: int, expense: int, balance: int}> 日付をキーとした収支配列
     */
    public function calculateDailyBalances(Collection $transactions): array
    {
        $dailyBalances = [];

        /** @var Collection<int, Collection<int, Transaction>> $grouped */
        $grouped = $transactions->groupBy(fn (Transaction $t): int => $t->date->day);

        foreach ($grouped as $day => $dayTransactions) {
            $dailyBalances[$day] = $this->calculateBalance($dayTransactions);
        }

        return $dailyBalances;
    }

    /**
     * 取引コレクションから収入・支出・差引残高を計算する.
     *
     * @param Collection<int, Transaction> $transactions 集計対象の取引コレクション
     *
     * @return array{income: int, expense: int, balance: int} 収入・支出・差引残高の連想配列
     */
    public function calculateBalance(Collection $transactions): array
    {
        $income  = (int) $transactions->where('type', FlowType::Income)->sum('amount');
        $expense = (int) $transactions->where('type', FlowType::Expense)->sum('amount');

        return [
            'income'  => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    /**
     * payer別の月次収支を計算する.
     *
     * @param Collection<int, Transaction> $transactions 月間の取引コレクション
     *
     * @return array<string, array{label: string, balance: int}> payer値をキーとした収支配列
     */
    public function calculatePayerBalances(Collection $transactions): array
    {
        $payerBalances = [];

        foreach (PayerType::cases() as $payer) {
            $payerTransactions = $transactions->where('payer', $payer);
            $income            = (int) $payerTransactions->where('type', FlowType::Income)->sum('amount');
            $expense           = (int) $payerTransactions->where('type', FlowType::Expense)->sum('amount');

            $payerBalances[$payer->value] = [
                'label'   => $payer->label(),
                'balance' => $income - $expense,
            ];
        }

        return $payerBalances;
    }
}
