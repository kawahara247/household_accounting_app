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
        // Arrange & Act
        $cases = FlowType::cases();

        // Assert
        $this->assertCount(2, $cases);
        $this->assertSame('income', FlowType::Income->value);
        $this->assertSame('expense', FlowType::Expense->value);
    }

    #[Test]
    public function FlowTypeのlabelメソッドは表示名を返す(): void
    {
        // Act & Assert
        $this->assertSame('収入', FlowType::Income->label());
        $this->assertSame('支出', FlowType::Expense->label());
    }
}
