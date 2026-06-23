<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FlowType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @param Builder<Category> $query
     */
    #[Scope]
    protected function byType(Builder $query, FlowType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FlowType::class,
        ];
    }
}
