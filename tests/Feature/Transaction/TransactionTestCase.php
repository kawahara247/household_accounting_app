<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class TransactionTestCase extends TestCase
{
    use RefreshDatabase;
}
