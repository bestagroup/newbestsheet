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
        Schema::create('roles', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا نقش');
            $table->string('title_fa')->nullable()->comment('عنوان نقش مانند مدیر، کاربر، ناظر');
            $table->string('title')->nullable()->comment('نام انگلیسی یا کد یکتا نقش برای استفاده در سیستم');
            $table->boolean('status')->nullable()->comment('وضعیت فعال = 4 و غیر فعال = 0');
            $table->unsignedBigInteger('user_id')->nullable()->comment('شناسه کاربری که این نقش را ایجاد کرده');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps(); // created_at و updated_at
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
