<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->comment('取引');
            $table->id()->comment('ID');
            $table->date('date')->comment('取引日');
            $table->string('type')->comment('種別（income: 収入 / expense: 支出）');
            $table->foreignId('category_id')->constrained()->comment('カテゴリID');
            $table->string('payer')->comment('支払元/受取人（person_a / person_b）');
            $table->integer('amount')->comment('金額');
            $table->string('memo')->nullable()->comment('メモ');
            $table->datetime('created_at')->nullable()->comment('作成日時');
            $table->datetime('updated_at')->nullable()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
