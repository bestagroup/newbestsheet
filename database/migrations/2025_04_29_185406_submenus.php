<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submenus', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا زیرصفحه');
            $table->unsignedBigInteger('priority')->nullable()->comment('شماره اولویت');
            $table->string('title')->nullable()->comment('عنوان زیرصفحه');
            $table->string('label')->nullable()->comment('برچسب نمایشی زیرمنو');
            $table->unsignedBigInteger('menu_id')->nullable()->comment('شناسه منو اصلی که این زیرصفحه به آن تعلق دارد');
            $table->foreign('menu_id')->references('id')->on('menus')->nullOnDelete();
            $table->string('slug')->nullable()->comment('آدرس زیرصفحه');
            $table->string('class')->nullable()->comment('کلاس لاراول');
            $table->string('controller')->nullable()->comment('کنترلر مرتبط با زیرصفحه');
            $table->string('tab_title')->nullable()->comment('کنترلر مرتبط با زیرصفحه');
            $table->string('page_title')->nullable()->comment('کنترلر مرتبط با زیرصفحه');
            $table->text('keyword')->nullable()->comment('کنترلر مرتبط با زیرصفحه');
            $table->text('description')->nullable()->comment('کنترلر مرتبط با زیرصفحه');
            $table->boolean('status')->default(0)->comment('وضعیت زیرصفحه (فعال/غیرفعال)');
            $table->unsignedBigInteger('user_id')->nullable()->comment('شناسه کاربری که این نقش را ایجاد کرده');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('viewcount')->default(0)->comment('شمارنده بازدید');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submenus');
    }
};
