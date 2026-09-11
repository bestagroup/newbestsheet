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
        Schema::create('visits', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا بازدید');
            $table->string('ip')->comment('آی‌پی بازدیدکننده');
            $table->string('page')->comment('شناسه صفحه بازدیدشده');
            $table->string('device')->comment('نوع دستگاه (موبایل، دسکتاپ و...)');
            $table->string('browser')->comment('مرورگر بازدیدکننده');
            $table->string('from_page')->comment('صفحه‌ای که بازدید از آن شروع شده');
            $table->string('to_page')->comment('صفحه مقصد بازدید');
            $table->timestamp('date')->useCurrent()->useCurrentOnUpdate()->comment('تاریخ و زمان بازدید');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
