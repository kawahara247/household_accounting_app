<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->category = Category::factory()->expense()->name('個人の出費')->create();
    }

    // ---- create ----

    #[Test]
    public function 未認証ユーザーはCSVインポートページにアクセスできない(): void
    {
        $response = $this->get(route('csv-import.create'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 認証済みユーザーはCSVインポートページにアクセスできる(): void
    {
        $response = $this->actingAs($this->user)->get(route('csv-import.create'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/ImportCsv')
                ->where('previewRows', null)
        );
    }

    // ---- preview ----

    #[Test]
    public function 未認証ユーザーはCSVプレビューを実行できない(): void
    {
        $file     = $this->makeCsvUploadedFile();
        $response = $this->post(route('csv-import.preview'), ['csv_file' => $file]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function CSVファイルをアップロードするとプレビューデータが返される(): void
    {
        $file = $this->makeCsvUploadedFile(implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","楽天ＳＰ　すき家　アプリ","本人","1回払い","1960","0","1960","1960","0","*"',
            '"2026/01/25","Ｓｕｉｃａチャージ（楽天ペイ）","本人","1回払い","5000","0","5000","5000","0","*"',
        ]));

        $response = $this->actingAs($this->user)->post(route('csv-import.preview'), [
            'csv_file' => $file,
        ]);

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/ImportCsv')
                ->has('previewRows', 2)
                ->where('previewRows.0.memo', '楽天ＳＰ　すき家　アプリ')
                ->where('previewRows.0.amount', 1960)
                ->where('previewRows.1.memo', 'Ｓｕｉｃａチャージ（楽天ペイ）')
                ->where('previewRows.1.amount', 5000)
        );
    }

    #[Test]
    public function 利用日が空の補足行はプレビューに含まれない(): void
    {
        $file = $this->makeCsvUploadedFile(implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","ANTHROPIC利用国USA","本人","1回払い","805","0","805","805","0","*"',
            '"","現地利用額　　　　　　　　　５．０００変換レート　１６１．０００円","","","","","","","",""',
            '"2026/01/25","楽天ＳＰ　すき家　アプリ","本人","1回払い","1960","0","1960","1960","0","*"',
        ]));

        $response = $this->actingAs($this->user)->post(route('csv-import.preview'), [
            'csv_file' => $file,
        ]);

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/ImportCsv')
                ->has('previewRows', 2)
        );
    }

    #[Test]
    public function CSVファイルなしでプレビューを実行するとバリデーションエラー(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.preview'), []);

        $response->assertSessionHasErrors('csv_file');
    }

    // ---- store ----

    #[Test]
    public function 未認証ユーザーはCSVインポートを実行できない(): void
    {
        $response = $this->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => $this->category->id]],
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 選択した取引をインポートできる(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [
                ['memo' => '楽天ＳＰ　すき家', 'amount' => 1960, 'category_id' => $this->category->id],
                ['memo' => 'Ｓｕｉｃａチャージ', 'amount' => 5000, 'category_id' => $this->category->id],
            ],
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseCount('transactions', 2);
    }

    #[Test]
    public function チェックを外した取引はインポートされない(): void
    {
        // Arrange: 3件のうち2件だけを選択してPOST（1件はチェックを外したという想定）
        $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [
                ['memo' => '取り込む取引A', 'amount' => 1000, 'category_id' => $this->category->id],
                ['memo' => '取り込む取引B', 'amount' => 2000, 'category_id' => $this->category->id],
                // '取り込まない取引C' はリクエストに含まれない
            ],
        ]);

        // Assert: 2件だけ登録され、除外した取引はDBに存在しない
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseMissing('transactions', ['memo' => '取り込まない取引C']);
    }

    #[Test]
    public function インポートされた取引の日付は指定した日付になる(): void
    {
        $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => $this->category->id]],
        ]);

        $this->assertDatabaseHas('transactions', [
            'date' => '2026-02-01',
            'memo' => 'テスト',
        ]);
    }

    #[Test]
    public function インポートされた取引のtypeはexpenseになる(): void
    {
        $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => $this->category->id]],
        ]);

        $this->assertDatabaseHas('transactions', ['type' => 'expense']);
    }

    #[Test]
    public function インポートされた取引の支払元はPersonAになる(): void
    {
        $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => $this->category->id]],
        ]);

        $this->assertDatabaseHas('transactions', [
            'payer' => PayerType::PersonA->value,
        ]);
    }

    #[Test]
    public function インポート時にdateは必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => $this->category->id]],
        ]);

        $response->assertSessionHasErrors('date');
    }

    #[Test]
    public function インポート時にtransactionsは必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date' => '2026-02-01',
        ]);

        $response->assertSessionHasErrors('transactions');
    }

    #[Test]
    public function transactionsが空配列の場合はバリデーションエラー(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [],
        ]);

        $response->assertSessionHasErrors('transactions');
    }

    // ---- 新規振る舞い: 行ごとカテゴリ・メモ編集対応 ----

    #[Test]
    public function 行ごとに指定したカテゴリでインポートされる(): void
    {
        // Arrange
        $food = Category::factory()->expense()->name('食費')->create();

        // Act
        $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [
                ['memo' => 'ランチ', 'amount' => 1000, 'category_id' => $food->id],
                ['memo' => '雑貨', 'amount' => 2000, 'category_id' => $this->category->id],
            ],
        ]);

        // Assert
        $this->assertDatabaseHas('transactions', [
            'memo'        => 'ランチ',
            'category_id' => $food->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'memo'        => '雑貨',
            'category_id' => $this->category->id,
        ]);
    }

    #[Test]
    public function category_idが指定されない場合はバリデーションエラー(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000]],
        ]);

        $response->assertSessionHasErrors('transactions.0.category_id');
    }

    #[Test]
    public function 存在しないcategory_idの場合はバリデーションエラー(): void
    {
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => 9999]],
        ]);

        $response->assertSessionHasErrors('transactions.0.category_id');
    }

    #[Test]
    public function incomeカテゴリのcategory_idの場合はバリデーションエラー(): void
    {
        // Arrange
        $income = Category::factory()->income()->name('給与')->create();

        // Act
        $response = $this->actingAs($this->user)->post(route('csv-import.store'), [
            'date'         => '2026-02-01',
            'transactions' => [['memo' => 'テスト', 'amount' => 1000, 'category_id' => $income->id]],
        ]);

        // Assert
        $response->assertSessionHasErrors('transactions.0.category_id');
    }

    #[Test]
    public function createページのpropsにexpenseカテゴリ一覧が含まれる(): void
    {
        // Arrange: expenseとincome両方を用意し、expenseのみpropsに渡ることを確認
        $food = Category::factory()->expense()->name('食費')->create();
        Category::factory()->income()->name('給与')->create();

        // Act
        $response = $this->actingAs($this->user)->get(route('csv-import.create'));

        // Assert
        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('categories', 2)
                ->where('categories.0.id', $this->category->id)
                ->where('categories.0.name', '個人の出費')
                ->where('categories.1.id', $food->id)
                ->where('categories.1.name', '食費')
        );
    }

    #[Test]
    public function previewページのpropsにexpenseカテゴリ一覧が含まれる(): void
    {
        // Arrange
        $food = Category::factory()->expense()->name('食費')->create();
        Category::factory()->income()->name('給与')->create();
        $file = $this->makeCsvUploadedFile();

        // Act
        $response = $this->actingAs($this->user)->post(route('csv-import.preview'), [
            'csv_file' => $file,
        ]);

        // Assert
        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('categories', 2)
                ->where('categories.0.id', $this->category->id)
                ->where('categories.0.name', '個人の出費')
                ->where('categories.1.id', $food->id)
                ->where('categories.1.name', '食費')
        );
    }

    #[Test]
    public function previewRowsの各行に既定category_idが埋め込まれる(): void
    {
        // Arrange: 2行のCSVを用意
        $file = $this->makeCsvUploadedFile(implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","楽天ＳＰ　すき家　アプリ","本人","1回払い","1960","0","1960","1960","0","*"',
            '"2026/01/25","Ｓｕｉｃａチャージ（楽天ペイ）","本人","1回払い","5000","0","5000","5000","0","*"',
        ]));

        // Act
        $response = $this->actingAs($this->user)->post(route('csv-import.preview'), [
            'csv_file' => $file,
        ]);

        // Assert: 既定値は「個人の出費」のID
        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('previewRows.0.category_id', $this->category->id)
                ->where('previewRows.1.category_id', $this->category->id)
        );
    }

    // ---- helpers ----

    private function makeCsvUploadedFile(string $content = ''): UploadedFile
    {
        if ($content === '') {
            $content = '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"' . "\n"
                . '"2026/01/01","テスト","本人","1回払い","1000","0","1000","1000","0","*"';
        }

        $path = tempnam(sys_get_temp_dir(), 'csv_feature_test_');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'test.csv', 'text/csv', null, true);
    }
}
