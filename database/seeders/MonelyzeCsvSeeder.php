<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MonelyzeCsvSeeder extends Seeder
{
    private const CSV_FILE = 'monelyze.csv';

    public function run(): void
    {
        if (Transaction::query()->exists()) {
            $this->command?->warn('transactions テーブルに既存データがあるため、CSVインポートをスキップしました。');

            return;
        }

        $path = base_path(self::CSV_FILE);
        if (! file_exists($path)) {
            $this->command?->warn('CSVファイルが見つかりません: ' . $path);

            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command?->warn('CSVファイルを開けませんでした: ' . $path);

            return;
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            $this->command?->warn('CSVヘッダーの読み込みに失敗しました。');

            return;
        }

        $index = array_flip($header);

        while (($row = fgetcsv($handle)) !== false) {
            $dateValue    = $this->getValue($row, $index, '日付');
            $categoryName = $this->getValue($row, $index, 'カテゴリー');

            if ($dateValue === '') {
                continue;
            }

            $expenseValue = $this->getValue($row, $index, '支出');
            $incomeValue  = $this->getValue($row, $index, '収入');
            $memo         = $this->getValue($row, $index, 'メモ');

            $flowType         = $incomeValue !== '' ? FlowType::Income : FlowType::Expense;
            $amount           = $this->parseAmount($incomeValue !== '' ? $incomeValue : $expenseValue);
            $payer            = PayerType::PersonB;
            $isSalaryCategory = false;

            if ($categoryName === 'あ給与') {
                $categoryName     = '給与';
                $flowType         = FlowType::Income;
                $payer            = PayerType::PersonA;
                $isSalaryCategory = true;
            } elseif ($categoryName === 'コ給与' || $categoryName === 'コ給料') {
                $categoryName     = '給与';
                $flowType         = FlowType::Income;
                $payer            = PayerType::PersonB;
                $isSalaryCategory = true;
            }

            if ($amount <= 0) {
                continue;
            }

            if ($categoryName === '') {
                $categoryName = 'その他支出';
                $flowType     = FlowType::Expense;
            }

            $category = Category::query()->where('name', $categoryName)->first();
            if (! $category && ! $isSalaryCategory) {
                $categoryName = 'その他支出';
                $flowType     = FlowType::Expense;
                $category     = Category::query()->where('name', $categoryName)->first();
            }

            if (! $category) {
                $category = Category::create([
                    'name' => $categoryName,
                    'type' => $flowType,
                ]);
            }

            Transaction::create([
                'date'        => Carbon::createFromFormat('Y/m/d', $dateValue)->toDateString(),
                'type'        => $flowType,
                'category_id' => $category->id,
                'payer'       => $payer,
                'amount'      => $amount,
                'memo'        => $memo !== '' ? $memo : null,
            ]);
        }

        fclose($handle);
    }

    /**
     * @param array<int, string> $row
     * @param array<string, int> $index
     */
    private function getValue(array $row, array $index, string $key): string
    {
        $position = $index[$key] ?? null;
        if ($position === null) {
            return '';
        }

        return trim((string) ($row[$position] ?? ''));
    }

    private function parseAmount(string $value): int
    {
        $normalized = preg_replace('/[^0-9]/', '', $value);

        return $normalized === '' ? 0 : (int) $normalized;
    }
}
