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
        Schema::create('company_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->comment('شرکت ');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('full_name')->comment('نام و نام خانوادگی عضو');
            $table->string('national_code')->comment('کد ملی عضو');
            $table->string('position')->comment('سمت در شرکت (مثلاً مدیرعامل، رئیس هیئت مدیره)');
            $table->string('role_type')->comment('نوع نقش: عضو هیئت مدیره یا نماینده قانونی');
            $table->string('start_date')->nullable()->comment('تاریخ شروع مسئولیت');
            $table->string('end_date')->nullable()->comment('تاریخ پایان مسئولیت (در صورت اتمام)');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال بودن عضو در حال حاضر');
            $table->timestamps();

            $table->index(['project_id', 'is_active'], 'cm_project_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_members');
    }
};
