<?php

declare(strict_types=1);

namespace Tests\Feature\RecurringTransaction;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class RecurringTransactionTestCase extends TestCase
{
    use RefreshDatabase;
}
