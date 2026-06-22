<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class FilterTest extends TransactionTestCase
{
    #[Test]
    public function カテゴリで取引一覧をフィルタリングできる(): void
    {
        $user              = User::factory()->create();
        $categoryFood      = Category::factory()->expense()->name('食費')->create();
        $categoryTransport = Category::factory()->expense()->name('交通費')->create();

        Transaction::factory()->forCategory($categoryFood)->amount(1000)->create();
        Transaction::factory()->forCategory($categoryTransport)->amount(500)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'category_id' => $categoryFood->id,
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.category_id', $categoryFood->id)
        );
    }

    #[Test]
    public function 支払元で取引一覧をフィルタリングできる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->forCategory($category)->payer(PayerType::PersonA)->amount(1000)->create();
        Transaction::factory()->forCategory($category)->payer(PayerType::PersonB)->amount(500)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'payer' => 'person_a',
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.payer', 'person_a')
        );
    }

    #[Test]
    public function 種別で取引一覧をフィルタリングできる(): void
    {
        $user            = User::factory()->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();

        Transaction::factory()->forCategory($expenseCategory)->amount(1000)->create();
        Transaction::factory()->forCategory($incomeCategory)->amount(50000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'type' => 'income',
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.type', 'income')
        );
    }

    #[Test]
    public function メモで取引一覧をフィルタリングできる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->forCategory($category)->amount(1000)->memo('ランチ代')->create();
        Transaction::factory()->forCategory($category)->amount(500)->memo('夕食代')->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'memo' => 'ランチ',
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.memo', 'ランチ代')
        );
    }

    #[Test]
    public function 複数のフィルターを組み合わせて取引一覧をフィルタリングできる(): void
    {
        $user              = User::factory()->create();
        $categoryFood      = Category::factory()->expense()->name('食費')->create();
        $categoryTransport = Category::factory()->expense()->name('交通費')->create();

        Transaction::factory()->forCategory($categoryFood)->payer(PayerType::PersonA)->amount(1000)->memo('ランチ代')->create();
        Transaction::factory()->forCategory($categoryFood)->payer(PayerType::PersonB)->amount(800)->memo('ランチ代')->create();
        Transaction::factory()->forCategory($categoryTransport)->payer(PayerType::PersonA)->amount(500)->memo('電車代')->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'category_id' => $categoryFood->id,
            'payer'       => 'person_a',
            'memo'        => 'ランチ',
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.category_id', $categoryFood->id)
                ->where('transactions.0.payer', 'person_a')
                ->where('transactions.0.memo', 'ランチ代')
        );
    }
}
