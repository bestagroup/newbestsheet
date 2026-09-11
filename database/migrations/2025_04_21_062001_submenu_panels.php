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
        Schema::create('submenu_panels', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا پنل زیرمنو');
            $table->unsignedBigInteger('priority')->nullable()->comment('شماره اولویت');
            $table->string('title')->comment('عنوان زیرمنو');
            $table->string('label')->comment('عنوان فارسی پنل منو');
            $table->foreignId('menu_id')->constrained('menu_panels')->onDelete('cascade')->comment('شناسه منو اصلی که این زیرمنو به آن تعلق دارد');
            $table->string('slug')->comment('آدرس زیرمنو یا شناسه منو');
            $table->string('level')->nullable()->comment('سطح نمایش');
            $table->string('class')->nullable()->comment('کلاس لاراول');
            $table->string('controller')->nullable()->comment('کنترلر مرتبط با زیرمنو');
            $table->boolean('status')->default(0)->comment('وضعیت زیرمنو (فعال/غیرفعال)');
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
        Schema::dropIfExists('submenu_panels');
    }
};
