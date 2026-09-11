<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investsteps', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان مرحله');
            $table->string('description')->comment('توضیحات الزام آور مرحله');
            $table->text('content')->comment('فرم های هر مرحله');
            $table->integer('status')->comment('وضعیت مرحله');
            $table->decimal('weight', 8, 3)->default(5)->comment('وزن نسبی مرحله در محاسبه پیشرفت');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investsteps');
    }
};
