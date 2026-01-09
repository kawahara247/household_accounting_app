<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRecurringTransactionsCommand extends Command
{
    protected $signature = 'transactions:generate-recurring
                            {--date= : 処理対象日（YYYY-MM-DD形式、デフォルト: 今日）}';

    protected $description = '定期取引から当日分の取引を自動生成する';

    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $dayOfMonth = $targetDate->day;

        $this->info("処理対象日: {$targetDate->format('Y-m-d')} (日: {$dayOfMonth})");

        $recurringTransactions = RecurringTransaction::with('category')
            ->where('is_active', true)
            ->where('day_of_month', $dayOfMonth)
            ->get();

        if ($recurringTransactions->isEmpty()) {
            $this->info('対象の定期取引はありません。');

            return Command::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($recurringTransactions, $targetDate, &$created, &$skipped): void {
            foreach ($recurringTransactions as $recurring) {
                // 当月分が既に生成済みかチェック
                $exists = Transaction::where('recurring_transaction_id', $recurring->id)
                    ->whereYear('date', $targetDate->year)
                    ->whereMonth('date', $targetDate->month)
                    ->exists();

                if ($exists) {
                    $this->line("  スキップ: {$recurring->name} (既に生成済み)");
                    $skipped++;

                    continue;
                }

                Transaction::create([
                    'date'                     => $targetDate,
                    'type'                     => $recurring->type,
                    'category_id'              => $recurring->category_id,
                    'payer'                    => $recurring->payer,
                    'amount'                   => $recurring->amount,
                    'memo'                     => $recurring->memo,
                    'recurring_transaction_id' => $recurring->id,
                ]);

                $this->line("  生成: {$recurring->name}");
                $created++;
            }
        });

        $this->info("完了: 生成 {$created} 件, スキップ {$skipped} 件");

        Log::info('定期取引生成完了', [
            'date'    => $targetDate->format('Y-m-d'),
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }
}
