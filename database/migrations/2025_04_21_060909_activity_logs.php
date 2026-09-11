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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا لاگ فعالیت');
            $table->unsignedBigInteger('user_id')->comment('شناسه کاربری که این نقش را ایجاد کرده');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('activity_type')->comment('نوع فعالیت (مثل ورود به سیستم، بروزرسانی، حذف، مشاهده و...)');
            $table->text('description')->nullable()->comment('توضیحات مربوط به فعالیت');
            $table->string('ip_address')->nullable()->comment('آدرس IP کاربر که فعالیت را انجام داده');
            $table->string('browser')->nullable()->comment('مرورگر کاربر');
            $table->string('device')->nullable()->comment('نوع دستگاه کاربر (موبایل، دسکتاپ و...)');
            $table->timestamp('activity_time')->useCurrent()->comment('زمان انجام فعالیت');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
