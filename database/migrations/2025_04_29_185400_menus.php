<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا پنل منو');
            $table->unsignedBigInteger('priority')->nullable()->comment('شماره اولویت');
            $table->string('title')->nullable()->comment('عنوان صفحه');
            $table->string('label')->nullable()->comment('برچسب نمایشی منو');
            $table->string('icon')->nullable()->comment('آیکن منو');
            $table->string('slug')->nullable()->comment('آدرس صفحه');
            $table->string('tab_title')->nullable()->comment('عنوان صفحه در تب');
            $table->string('page_title')->nullable()->comment('عنوان صفحه در داخل صفحه');
            $table->boolean('submenu')->default(0)->comment('آیا زیرصفحه دارد؟');
            $table->string('class')->nullable()->comment('کلاس لاراول');
            $table->string('controller')->nullable()->comment('کنترلر مرتبط با صفحه');
            $table->boolean('status')->default(0)->comment('وضعیت نمایش صفحه (فعال/غیرفعال)');
            $table->boolean('home_show')->default(0)->comment('وضعیت نمایش در صفحه اصلی (فعال/غیرفعال)');
            $table->text('keyword')->nullable()->comment('کلمات کلیدی صفحه');
            $table->text('description')->nullable()->comment('توضیحات صفحه');
            $table->unsignedBigInteger('user_id')->nullable()->comment('شناسه کاربری ایجاد کننده');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('viewcount')->default(0)->comment('شمارنده بازدید');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
