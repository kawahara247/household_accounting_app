<?php

declare(strict_types=1);

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->comment('定期取引');
            $table->id()->comment('ID');
            $table->string('name')->comment('定期取引名');
            $table->unsignedTinyInteger('day_of_month')->comment('毎月の登録日（1-28）');
            $table->string('type')->comment('種別（income: 収入 / expense: 支出）');
            $table->foreignIdFor(Category::class)->constrained()->comment('カテゴリID');
            $table->string('payer')->comment('支払元/受取人（person_a / person_b）');
            $table->integer('amount')->comment('金額');
            $table->string('memo')->nullable()->comment('メモ');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->datetime('created_at')->nullable()->comment('作成日時');
            $table->datetime('updated_at')->nullable()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
