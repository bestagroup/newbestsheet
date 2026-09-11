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
        Schema::create('owners', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا شرکت');
            $table->string('title')->comment('عنوان شرکت');
            $table->text('tel')->nullable()->comment('تلفن');
            $table->string('mobile', 20)->nullable()->comment('شماره موبایل');
            $table->string('email', 191)->nullable()->comment('ایمیل');
            $table->string('ceo', 191)->nullable()->comment('نام مدیرعامل');
            $table->string('meli_code', 20)->nullable()->comment('کد ملی شرکت');
            $table->string('eghtesadi_code', 26)->nullable()->comment('کد اقتصادی');
            $table->string('date_sabt', 12)->nullable()->comment('تاریخ ثبت شرکت');
            $table->text('address')->nullable()->comment('آدرس');
            $table->text('social')->nullable()->comment('شبکه‌های اجتماعی به صورت JSON یا متن');
            $table->string('image')->comment('لوگوی اصلی');
            $table->string('favicon16')->nullable()->comment('آیکون ۱۶×۱۶');
            $table->string('favicon32')->nullable()->comment('آیکون ۳۲×۳۲');
            $table->text('summery')->nullable()->comment('خلاصه معرفی شرکت');
            $table->unsignedBigInteger('user_id')->comment('شناسه کاربری که این نقش را ایجاد کرده');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
