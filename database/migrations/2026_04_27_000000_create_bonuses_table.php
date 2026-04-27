<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonuses', function (Blueprint $table) {
            $table->comment('ボーナス');
            $table->id()->comment('ID');
            $table->string('year_month', 7)->comment('対象年月（YYYY-MM）');
            $table->string('payer')->comment('受取人（person_a / person_b）');
            $table->integer('amount')->comment('金額');
            $table->datetime('created_at')->nullable()->comment('作成日時');
            $table->datetime('updated_at')->nullable()->comment('更新日時');
            $table->unique(['year_month', 'payer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};
