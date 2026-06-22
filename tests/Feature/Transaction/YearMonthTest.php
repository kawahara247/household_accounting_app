<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class YearMonthTest extends TransactionTestCase
{
    #[Test]
    public function 取引一覧は年月フィルターパラメータを受け取る(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->forCategory($category)->on('2026-01-10')->amount(1000)->create();
        Transaction::factory()->forCategory($category)->on('2026-02-10')->amount(2000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['year_month' => '2026-01']));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', 1000)
                ->where('filters.year_month', '2026-01')
        );
    }

    #[Test]
    public function 取引一覧はデフォルトで現在の年月でフィルターされる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        // 現在月（travelTo で 2026-06 固定）
        Transaction::factory()->forCategory($category)->on(now()->format('Y-m-d'))->amount(3000)->create();
        // 1ヶ月前
        Transaction::factory()->forCategory($category)->on(now()->subMonth()->format('Y-m-d'))->amount(5000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', 3000)
                ->where('filters.year_month', now()->format('Y-m'))
        );
    }

    #[Test]
    public function 年月フィルターは他のフィルターと組み合わせられる(): void
    {
        $user              = User::factory()->create();
        $foodCategory      = Category::factory()->expense()->name('食費')->create();
        $transportCategory = Category::factory()->expense()->name('交通費')->create();

        Transaction::factory()->forCategory($foodCategory)->on('2026-01-10')->amount(1000)->create();
        Transaction::factory()->forCategory($transportCategory)->on('2026-01-15')->amount(500)->create();
        Transaction::factory()->forCategory($foodCategory)->on('2026-02-10')->amount(2000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'year_month'  => '2026-01',
            'category_id' => $foodCategory->id,
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', 1000)
                ->where('transactions.0.category_id', $foodCategory->id)
                ->where('filters.year_month', '2026-01')
                ->where('filters.category_id', $foodCategory->id)
        );
    }

    #[Test]
    public function year_monthに空文字を指定すると全期間の取引が返される(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->forCategory($category)->on(now()->subMonth()->format('Y-m-d'))->amount(1000)->create();
        Transaction::factory()->forCategory($category)->on(now()->format('Y-m-d'))->amount(2000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['year_month' => '']));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 2)
                ->where('filters.year_month', null)
        );
    }

    #[Test]
    public function 取引一覧にyearMonthsプロパティが返される(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();
        Transaction::factory()->forCategory($category)->on('2026-01-10')->amount(1000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['year_month' => '']));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('yearMonths')
                ->where('yearMonths', fn ($ym) => collect($ym)->contains('2026-01'))
        );
    }
}
