<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class IndexPropsTest extends TransactionTestCase
{
    #[Test]
    public function 取引一覧画面はカテゴリ一覧をプロップスで返す(): void
    {
        $user = User::factory()->create();
        Category::factory()->expense()->name('食費')->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('categories', 1)
                ->has(
                    'categories.0',
                    fn (Assert $cat) => $cat
                        ->where('name', '食費')
                        ->where('type', 'expense')
                        ->etc()
                )
        );
    }

    #[Test]
    public function 取引一覧画面はpayer一覧をプロップスで返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('payers', 2)
                ->has(
                    'payers.0',
                    fn (Assert $payer) => $payer
                        ->has('value')
                        ->has('label')
                )
        );
    }

    #[Test]
    public function 取引一覧のdate項目はYYYY_MM_DD形式で返される(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();
        Transaction::factory()->forCategory($category)->on('2026-01-04')->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['year_month' => '2026-01']));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.date', '2026-01-04')
        );
    }
}
