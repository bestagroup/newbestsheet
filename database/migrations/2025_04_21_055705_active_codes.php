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
        Schema::create('active_codes', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا کد فعال‌سازی');
            $table->unsignedBigInteger('user_id')->comment('شناسه کاربری که این نقش را ایجاد کرده');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('code')->comment('کد فعال‌سازی');
            $table->timestamp('expired_at')->useCurrent()->useCurrentOnUpdate()->comment('تاریخ انقضای کد، به‌روزرسانی خودکار در هر آپدیت');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_codes');
    }
};
