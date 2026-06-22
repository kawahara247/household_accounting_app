<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Services\TrendAggregator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\TestCase;

class TrendAggregatorTest extends TestCase
{
    private TrendAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregator = new TrendAggregator;
    }

    #[Test]
    public function generateMonthRangeは開始月から終了月までを返す(): void
    {
        $labels = $this->aggregator->generateMonthRange('2026-01', '2026-03');

        $this->assertSame(['2026-01', '2026-02', '2026-03'], $labels);
    }

    #[Test]
    public function generateMonthRangeは開始月と終了月が同じなら1要素を返す(): void
    {
        $labels = $this->aggregator->generateMonthRange('2026-05', '2026-05');

        $this->assertSame(['2026-05'], $labels);
    }

    #[Test]
    public function generateMonthRangeは年をまたぐ範囲も返せる(): void
    {
        $labels = $this->aggregator->generateMonthRange('2025-11', '2026-02');

        $this->assertSame(['2025-11', '2025-12', '2026-01', '2026-02'], $labels);
    }

    #[Test]
    public function buildMergedDatasetsはカテゴリ毎に月別合計を返す(): void
    {
        $food      = $this->makeCategory(1, '食費');
        $transport = $this->makeCategory(2, '交通費');

        $rows = new Collection([
            $this->row(['year_month' => '2026-01', 'category_id' => 1, 'total' => 15000]),
            $this->row(['year_month' => '2026-02', 'category_id' => 2, 'total' => 3000]),
        ]);

        $datasets = $this->aggregator->buildMergedDatasets(
            ['2026-01', '2026-02'],
            collect([$food, $transport]),
            $rows,
        );

        $this->assertSame([
            ['name' => '食費', 'data' => [15000, 0]],
            ['name' => '交通費', 'data' => [0, 3000]],
        ], $datasets);
    }

    #[Test]
    public function buildMergedDatasetsはデータがない月を0で埋める(): void
    {
        $food = $this->makeCategory(1, '食費');

        $rows = new Collection([
            $this->row(['year_month' => '2026-02', 'category_id' => 1, 'total' => 8000]),
        ]);

        $datasets = $this->aggregator->buildMergedDatasets(
            ['2026-01', '2026-02', '2026-03'],
            collect([$food]),
            $rows,
        );

        $this->assertSame([0, 8000, 0], $datasets[0]['data']);
    }

    #[Test]
    public function buildMergedBalanceDatasetsは収入支出差引を返す(): void
    {
        $rows = new Collection([
            $this->row(['year_month' => '2026-01', 'income_total' => 300000, 'expense_total' => 50000]),
        ]);

        $datasets = $this->aggregator->buildMergedBalanceDatasets(['2026-01'], $rows);

        $this->assertSame('収支', $datasets[0]['name']);
        $this->assertSame([250000], $datasets[0]['data']);
    }

    #[Test]
    public function buildMergedBalanceDatasetsは収入がない月は支出の負値になる(): void
    {
        $rows = new Collection([
            $this->row(['year_month' => '2026-01', 'income_total' => 0, 'expense_total' => 50000]),
        ]);

        $datasets = $this->aggregator->buildMergedBalanceDatasets(['2026-01'], $rows);

        $this->assertSame([-50000], $datasets[0]['data']);
    }

    #[Test]
    public function buildMergedBalanceDatasetsは取引のない月は0になる(): void
    {
        $datasets = $this->aggregator->buildMergedBalanceDatasets(
            ['2026-01', '2026-02'],
            new Collection,
        );

        $this->assertSame([0, 0], $datasets[0]['data']);
    }

    #[Test]
    public function buildPayerSplitDatasetsはカテゴリxpayer分のデータセットを返す(): void
    {
        $food      = $this->makeCategory(1, '食費');
        $transport = $this->makeCategory(2, '交通費');

        $rows = new Collection([
            $this->row(['year_month' => '2026-01', 'category_id' => 1, 'payer' => 'person_a', 'total' => 3000]),
            $this->row(['year_month' => '2026-01', 'category_id' => 2, 'payer' => 'person_b', 'total' => 1000]),
        ]);

        $datasets = $this->aggregator->buildPayerSplitDatasets(
            ['2026-01'],
            collect([$food, $transport]),
            $rows,
        );

        // カテゴリ2件 × payer 2件 = 4件
        $this->assertCount(4, $datasets);

        $byKey = collect($datasets)->keyBy(fn (array $d): string => $d['name'] . ':' . $d['payer']);
        $this->assertSame([3000], $byKey['食費:person_a']['data']);
        $this->assertSame([0], $byKey['食費:person_b']['data']);
        $this->assertSame([0], $byKey['交通費:person_a']['data']);
        $this->assertSame([1000], $byKey['交通費:person_b']['data']);
    }

    #[Test]
    public function buildPayerSplitBalanceDatasetsはpayer別収支を返す(): void
    {
        $rows = new Collection([
            $this->row(['year_month' => '2026-01', 'payer' => 'person_a', 'income_total' => 300000, 'expense_total' => 50000]),
            $this->row(['year_month' => '2026-01', 'payer' => 'person_b', 'income_total' => 0, 'expense_total' => 30000]),
        ]);

        $datasets = $this->aggregator->buildPayerSplitBalanceDatasets(['2026-01'], $rows);

        $byPayer = collect($datasets)->keyBy('payer');
        $this->assertSame([250000], $byPayer['person_a']['data']);
        $this->assertSame([-30000], $byPayer['person_b']['data']);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function row(array $attrs): stdClass
    {
        return (object) $attrs;
    }

    private function makeCategory(int $id, string $name): Category
    {
        $category       = new Category;
        $category->id   = $id;
        $category->name = $name;

        return $category;
    }
}
