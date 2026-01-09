<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\FlowType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlowTypeTest extends TestCase
{
    #[Test]
    public function FlowTypeはIncomeとExpenseの2つの値を持つ(): void
    {
        // Arrange & Act: Enumの全ケースを取得
        $cases = FlowType::cases();

        // Assert: 2つの値（Income, Expense）が定義されている
        $this->assertCount(2, $cases);
        $this->assertSame('income', FlowType::Income->value);
        $this->assertSame('expense', FlowType::Expense->value);
    }

    #[Test]
    public function FlowTypeのlabelメソッドは表示名を返す(): void
    {
        // Arrange: なし（Enumの静的メソッドをテスト）

        // Act & Assert: labelメソッドが日本語表示名を返す
        $this->assertSame('収入', FlowType::Income->label());
        $this->assertSame('支出', FlowType::Expense->label());
    }
}
