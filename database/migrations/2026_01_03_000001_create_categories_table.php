<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->comment('カテゴリ');
            $table->id()->comment('ID');
            $table->string('name')->comment('カテゴリ名');
            $table->string('type')->comment('種別（income: 収入 / expense: 支出）');
            $table->string('icon')->nullable()->comment('アイコン');
            $table->string('color')->nullable()->comment('表示色');
            $table->datetime('created_at')->nullable()->comment('作成日時');
            $table->datetime('updated_at')->nullable()->comment('更新日時');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
