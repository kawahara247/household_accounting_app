<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FlowType;
use App\Enums\PayerType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property Carbon $date
 * @property FlowType $type
 * @property int $category_id
 * @property PayerType $payer
 * @property int $amount
 * @property string|null $memo
 * @property int|null $recurring_transaction_id
 */
class Transaction extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<RecurringTransaction, $this>
     */
    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    /**
     * フィルター条件を適用するスコープ
     *
     * @param Builder<Transaction> $query
     * @param array{category_id?: int|string|null, payer?: string|null, type?: string|null, memo?: string|null, year_month?: string|null} $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['category_id'] ?? null, fn(Builder $q, int|string $categoryId) => $q->where('category_id', $categoryId))
            ->when($filters['payer'] ?? null, fn(Builder $q, string $payer) => $q->where('payer', $payer))
            ->when($filters['type'] ?? null, fn(Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['memo'] ?? null, fn(Builder $q, string $memo) => $q->where('memo', 'like', "%{$memo}%"))
            ->when($filters['year_month'] ?? null, fn(Builder $q, string $yearMonth) => $q->whereYear('date', substr($yearMonth, 0, 4))
                ->whereMonth('date', substr($yearMonth, 5, 2)));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date'  => 'date',
            'type'  => FlowType::class,
            'payer' => PayerType::class,
        ];
    }
}
