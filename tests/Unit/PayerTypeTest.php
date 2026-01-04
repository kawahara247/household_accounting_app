<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PayerType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayerTypeTest extends TestCase
{
    #[Test]
    public function PayerTypeはPersonAとPersonBの2つの値を持つ(): void
    {
        // Arrange & Act
        $cases = PayerType::cases();

        // Assert
        $this->assertCount(2, $cases);
        $this->assertSame('person_a', PayerType::PersonA->value);
        $this->assertSame('person_b', PayerType::PersonB->value);
    }

    #[Test]
    public function PayerTypeのlabelメソッドは設定ファイルから表示名を取得する(): void
    {
        // Arrange
        config(['payers.person_a' => 'テスト名A']);
        config(['payers.person_b' => 'テスト名B']);

        // Act & Assert
        $this->assertSame('テスト名A', PayerType::PersonA->label());
        $this->assertSame('テスト名B', PayerType::PersonB->label());
    }
}
