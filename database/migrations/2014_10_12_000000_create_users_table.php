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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا کاربر');
            $table->string('name')->nullable()->comment('نام کامل کاربر');
            $table->string('username')->nullable()->comment('نام کاربری برای ورود');
            $table->string('email')->nullable()->comment('آدرس ایمیل');
            $table->boolean('email_verify')->default(0)->comment('تایید ایمیل (0=تایید نشده، 1=تایید شده)');
            $table->timestamp('email_verified_at')->nullable()->comment('تاریخ و زمان تایید ایمیل');
            $table->string('level', 20)->default('site')->comment('سطح دسترسی کاربر مانند site یا panel');
            $table->string('api_token')->nullable()->comment('توکن API برای دسترسی از طریق کلاینت‌ها');
            $table->string('password')->nullable()->comment('رمز عبور هش شده');
            $table->string('image')->nullable()->comment('آدرس تصویر پروفایل');
            $table->string('phone')->nullable()->comment('شماره تلفن همراه');
            $table->boolean('phone_verify')->nullable()->comment('وضعیت تایید شماره همراه');
            $table->string('telphone')->nullable()->comment('شماره تلفن ثابت');
            $table->string('national_id', 10)->nullable()->comment('کد ملی کاربر');
            $table->string('user_job', 191)->nullable()->comment('عنوان شغلی کاربر');
            $table->string('marital_status', 191)->nullable()->comment('وضعیت تاهل کاربر');
            $table->unsignedBigInteger('type_id')->nullable()->comment('شناسه نوع کاربر');
            $table->unsignedBigInteger('role_id')->nullable()->comment('شناسه نوع کاربر');
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->foreign('type_id')->references('id')->on('type_users')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->string('birthday')->nullable()->comment('تاریخ تولد به فرمت میلادی یا شمسی');
            $table->integer('gender')->nullable()->comment('جنسیت (مثلاً 1=مرد، 2=زن)');
            $table->integer('age')->nullable()->comment('سن');
            $table->string('originality')->nullable()->comment('اصالت یا محل تولد کاربر');
            $table->string('postalcode', 20)->nullable()->comment('کد پستی');
            $table->string('father_name')->nullable()->comment('نام پدر');
            $table->string('birth_certificate')->nullable()->comment('شماره شناسنامه');
            $table->string('job_title')->nullable()->comment('عنوان شغلی دقیق');
            $table->bigInteger('education_id')->nullable()->comment('مدرک تحصیلی یا مقطع تحصیلی');
            $table->bigInteger('folder_id')->nullable()->comment('شناسه پرونده کاربر');
            $table->string('folder_validity')->nullable()->comment('تاریخ اعتبار پرونده');
            $table->bigInteger('folder_base')->nullable()->comment('مبنا یا ریشه پرونده');
            $table->bigInteger('place_id')->nullable()->comment('محل صدور شناسنامه یا محل خاص دیگر');
            $table->text('address')->nullable()->comment('آدرس کامل محل سکونت');
            $table->text('social')->nullable()->comment('شبکه‌های اجتماعی به صورت JSON یا متن');
            $table->text('bio')->nullable()->comment('بیوگرافی یا توضیحات درباره کاربر');
            $table->boolean('status')->nullable()->comment('وضعیت کاربر (فعال یا غیرفعال)');
            $table->text('google_id')->nullable()->comment('شناسه پایدار کاربر در Google OAuth');
            $table->rememberToken()->comment('توکن یادآوری برای ورودهای بعدی');
            $table->timestamps(); // created_at و updated_at
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
