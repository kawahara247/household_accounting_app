<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrendTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 未認証ユーザーはリダイレクトされる(): void
    {
        $response = $this->get(route('trends.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 認証済みユーザーは推移グラフページを表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index'));

        $response->assertInertia(
            fn (Assert $page) => $page->component('Trends/Index')
        );
    }

    #[Test]
    public function デフォルトでexpenseと過去12ヶ月分が設定される(): void
    {
        $this->travelTo('2026-05-24');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('type', 'expense')
                ->where('filters.start_month', '2025-06')
                ->where('filters.end_month', '2026-05')
        );
    }

    #[Test]
    public function typeパラメータでincomeを指定できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index', ['type' => 'income']));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('type', 'income')
        );
    }

    #[Test]
    public function 指定した期間のlabelsが生成される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index', [
            'start_month' => '2026-01',
            'end_month'   => '2026-03',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('labels', ['2026-01', '2026-02', '2026-03'])
        );
    }

    #[Test]
    public function カテゴリ別月別合計がdatasetsに含まれる(): void
    {
        $user = User::factory()->create();

        $food      = Category::create(['name' => '食費', 'type' => FlowType::Expense]);
        $transport = Category::create(['name' => '交通費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 10000,
        ]);
        Transaction::create([
            'date'        => '2026-01-20',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 5000,
        ]);
        Transaction::create([
            'date'        => '2026-02-05',
            'type'        => FlowType::Expense,
            'category_id' => $transport->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 3000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'        => 'expense',
            'start_month' => '2026-01',
            'end_month'   => '2026-02',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('datasets', 2)
                ->has(
                    'datasets.0',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '食費')
                        ->where('data', [15000, 0])
                )
                ->has(
                    'datasets.1',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '交通費')
                        ->where('data', [0, 3000])
                )
        );
    }

    #[Test]
    public function データがない月は0になる(): void
    {
        $user = User::factory()->create();
        $food = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-02-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 8000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'        => 'expense',
            'start_month' => '2026-01',
            'end_month'   => '2026-03',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has(
                    'datasets.0',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '食費')
                        ->where('data', [0, 8000, 0])
                )
        );
    }

    #[Test]
    public function 収入カテゴリはtypeがincomeの取引のみ集計される(): void
    {
        $user = User::factory()->create();

        $salary  = Category::create(['name' => '給与', 'type' => FlowType::Income]);
        $expense = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-25',
            'type'        => FlowType::Income,
            'category_id' => $salary->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 300000,
        ]);
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $expense->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 5000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'        => 'income',
            'start_month' => '2026-01',
            'end_month'   => '2026-01',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('datasets', 1)
                ->has(
                    'datasets.0',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '給与')
                        ->where('data', [300000])
                )
        );
    }

    #[Test]
    public function availableMonthsに取引のある月一覧が含まれる(): void
    {
        $user = User::factory()->create();
        $food = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);
        Transaction::create([
            'date'        => '2026-03-15',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 2000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('availableMonths')
                ->where(
                    'availableMonths',
                    fn ($months) => collect($months)->contains('2026-01') && collect($months)->contains('2026-03')
                )
        );
    }

    #[Test]
    public function availableMonthsにstart_monthとend_monthが含まれる(): void
    {
        $this->travelTo('2026-05-24');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index', [
            'start_month' => '2026-02',
            'end_month'   => '2026-05',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where(
                    'availableMonths',
                    fn ($months) => collect($months)->contains('2026-02') && collect($months)->contains('2026-05')
                )
        );
    }

    #[Test]
    public function payersプロパティが返される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('payers', 2)
                ->has(
                    'payers.0',
                    fn (Assert $payer) => $payer->has('value')->has('label')
                )
        );
    }

    #[Test]
    public function splitByPayerがデフォルトでfalseで返される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('splitByPayer', false)
        );
    }

    #[Test]
    public function splitByPayerがtrueのときpayer別データセットが返される(): void
    {
        $user = User::factory()->create();

        $food = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 10000,
        ]);
        Transaction::create([
            'date'        => '2026-01-15',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 5000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'           => 'expense',
            'start_month'    => '2026-01',
            'end_month'      => '2026-01',
            'split_by_payer' => '1',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('splitByPayer', true)
                ->has('datasets', 2)
                ->has(
                    'datasets.0',
                    fn (Assert $ds) => $ds
                        ->where('name', '食費')
                        ->where('payer', 'person_a')
                        ->has('payerLabel')
                        ->where('data', [10000])
                )
                ->has(
                    'datasets.1',
                    fn (Assert $ds) => $ds
                        ->where('name', '食費')
                        ->where('payer', 'person_b')
                        ->has('payerLabel')
                        ->where('data', [5000])
                )
        );
    }

    #[Test]
    public function typeパラメータでbalanceを指定できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index', ['type' => 'balance']));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('type', 'balance')
        );
    }

    #[Test]
    public function 収支は収入合計から支出合計を差し引いた値になる(): void
    {
        $user = User::factory()->create();

        $salary = Category::create(['name' => '給与', 'type' => FlowType::Income]);
        $food   = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-25',
            'type'        => FlowType::Income,
            'category_id' => $salary->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 300000,
        ]);
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'        => 'balance',
            'start_month' => '2026-01',
            'end_month'   => '2026-01',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('datasets', 1)
                ->has(
                    'datasets.0',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '収支')
                        ->where('data', [250000])
                )
        );
    }

    #[Test]
    public function 収入がない月は支出の負値になる(): void
    {
        $user = User::factory()->create();

        $food = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'        => 'balance',
            'start_month' => '2026-01',
            'end_month'   => '2026-01',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has(
                    'datasets.0',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '収支')
                        ->where('data', [-50000])
                )
        );
    }

    #[Test]
    public function 収支で取引がない月は0になる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'        => 'balance',
            'start_month' => '2026-01',
            'end_month'   => '2026-02',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has(
                    'datasets.0',
                    fn (Assert $dataset) => $dataset
                        ->where('name', '収支')
                        ->where('data', [0, 0])
                )
        );
    }

    #[Test]
    public function 収支でsplitByPayerがtrueのとき支払人別収支データセットが返される(): void
    {
        $user = User::factory()->create();

        $salary = Category::create(['name' => '給与', 'type' => FlowType::Income]);
        $food   = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-25',
            'type'        => FlowType::Income,
            'category_id' => $salary->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 300000,
        ]);
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);
        Transaction::create([
            'date'        => '2026-01-15',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 30000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'           => 'balance',
            'start_month'    => '2026-01',
            'end_month'      => '2026-01',
            'split_by_payer' => '1',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->where('splitByPayer', true)
                ->has('datasets', 2)
                ->has(
                    'datasets.0',
                    fn (Assert $ds) => $ds
                        ->where('name', '収支')
                        ->where('payer', 'person_a')
                        ->has('payerLabel')
                        ->where('data', [250000])
                )
                ->has(
                    'datasets.1',
                    fn (Assert $ds) => $ds
                        ->where('name', '収支')
                        ->where('payer', 'person_b')
                        ->has('payerLabel')
                        ->where('data', [-30000])
                )
        );
    }

    #[Test]
    public function 収支でsplitByPayerがtrueのときデータセット数はPayerTypeのケース数と一致する(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'           => 'balance',
            'start_month'    => '2026-01',
            'end_month'      => '2026-01',
            'split_by_payer' => '1',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('datasets', count(PayerType::cases()))
        );
    }

    #[Test]
    public function splitByPayerがtrueのとき複数カテゴリはカテゴリxpayer分のデータセットになる(): void
    {
        $user = User::factory()->create();

        $food      = Category::create(['name' => '食費', 'type' => FlowType::Expense]);
        $transport = Category::create(['name' => '交通費', 'type' => FlowType::Expense]);

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $food->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 3000,
        ]);
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $transport->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('trends.index', [
            'type'           => 'expense',
            'start_month'    => '2026-01',
            'end_month'      => '2026-01',
            'split_by_payer' => '1',
        ]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Trends/Index')
                ->has('datasets', 4)
        );
    }
}
