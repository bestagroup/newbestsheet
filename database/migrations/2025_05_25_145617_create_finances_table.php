<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finances', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا پنل منو');
            $table->unsignedBigInteger('project_id')->nullable()->comment('پروژه ');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->text('description')->nullable()->comment('توضیحات پرداخت');
            $table->string('amount')->nullable()->comment('مبلغ پرداختی');
            $table->string('date')->nullable()->comment('تاریخ واریز');
            $table->string('serial')->nullable()->comment('شماره سند /شماره چک ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finances');
    }
};
