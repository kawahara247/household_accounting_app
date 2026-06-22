<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const TEST_NOW = '2026-06-15 00:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(static::TEST_NOW);
    }
}
