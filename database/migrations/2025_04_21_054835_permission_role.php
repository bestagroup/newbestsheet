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
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا رکورد');
            $table->unsignedBigInteger('role_id')->comment('نقش کاربر');
            $table->unsignedBigInteger('permission_id')->comment('سطح کاربر');
            $table->boolean('can_view')->default(false)->comment('مجوز نمایش');
            $table->boolean('can_insert')->default(false)->comment('مجوز افزودن');
            $table->boolean('can_edit')->default(false)->comment('مجوز ویرایش');
            $table->boolean('can_delete')->default(false)->comment('مجوز پاک کردن');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
