<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CsvImportService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvImportServiceTest extends TestCase
{
    private CsvImportService $service;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CsvImportService;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    #[Test]
    public function 通常の行を正しくパースできる(): void
    {
        // Arrange: 楽天e-NAVIフォーマットのCSVを作成
        $csvContent = implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","楽天ＳＰ　すき家　アプリ","本人","1回払い","1960","0","1960","1960","0","*"',
            '"2026/01/25","Ｓｕｉｃａチャージ（楽天ペイ）","本人","1回払い","5000","0","5000","5000","0","*"',
        ]);
        $filePath = $this->createTempFile($csvContent);

        // Act
        $rows = $this->service->parseCreditCardCsv($filePath);

        // Assert
        $this->assertCount(2, $rows);
        $this->assertSame('楽天ＳＰ　すき家　アプリ', $rows[0]['memo']);
        $this->assertSame(1960, $rows[0]['amount']);
        $this->assertSame('Ｓｕｉｃａチャージ（楽天ペイ）', $rows[1]['memo']);
        $this->assertSame(5000, $rows[1]['amount']);
    }

    #[Test]
    public function 利用日が空の行はスキップされる(): void
    {
        // Arrange: 利用日が空の補足行（為替レート等）を含むCSV
        $csvContent = implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","ANTHROPIC利用国USA","本人","1回払い","805","0","805","805","0","*"',
            '"","現地利用額　　　　　　　　　５．０００変換レート　１６１．０００円","","","","","","","",""',
            '"2026/01/25","楽天ＳＰ　すき家　アプリ","本人","1回払い","1960","0","1960","1960","0","*"',
        ]);
        $filePath = $this->createTempFile($csvContent);

        // Act
        $rows = $this->service->parseCreditCardCsv($filePath);

        // Assert: 補足行はスキップされ2行のみ返る
        $this->assertCount(2, $rows);
        $this->assertSame('ANTHROPIC利用国USA', $rows[0]['memo']);
        $this->assertSame('楽天ＳＰ　すき家　アプリ', $rows[1]['memo']);
    }

    #[Test]
    public function 金額は整数に変換される(): void
    {
        // Arrange
        $csvContent = implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","テスト","本人","1回払い","1234","0","1234","1234","0","*"',
        ]);
        $filePath = $this->createTempFile($csvContent);

        // Act
        $rows = $this->service->parseCreditCardCsv($filePath);

        // Assert
        $this->assertSame(1234, $rows[0]['amount']);
        $this->assertIsInt($rows[0]['amount']);
    }

    #[Test]
    public function 返却値の構造はmemoとamountのみ(): void
    {
        // Arrange
        $csvContent = implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","テスト店舗","本人","1回払い","1000","0","1000","1000","0","*"',
        ]);
        $filePath = $this->createTempFile($csvContent);

        // Act
        $rows = $this->service->parseCreditCardCsv($filePath);

        // Assert: dateは含まれずmemoとamountのみ
        $this->assertArrayHasKey('memo', $rows[0]);
        $this->assertArrayHasKey('amount', $rows[0]);
        $this->assertArrayNotHasKey('date', $rows[0]);
        $this->assertCount(2, $rows[0]);
    }

    #[Test]
    public function Shift_JISエンコードのCSVを読み込める(): void
    {
        // Arrange: Shift-JISエンコードのCSVファイルを作成
        $csvContentUtf8 = implode("\n", [
            '"利用日","利用店名・商品名","利用者","支払方法","利用金額","手数料/利息","支払総額","2月支払金額","3月繰越残高","新規サイン"',
            '"2026/01/29","すき家","本人","1回払い","500","0","500","500","0","*"',
        ]);
        $csvContentSjis = mb_convert_encoding($csvContentUtf8, 'SJIS-win', 'UTF-8');
        $filePath       = $this->createTempFile($csvContentSjis);

        // Act
        $rows = $this->service->parseCreditCardCsv($filePath);

        // Assert: Shift-JISでも正しくパースできる
        $this->assertCount(1, $rows);
        $this->assertSame('すき家', $rows[0]['memo']);
        $this->assertSame(500, $rows[0]['amount']);
    }

    private function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }
}
