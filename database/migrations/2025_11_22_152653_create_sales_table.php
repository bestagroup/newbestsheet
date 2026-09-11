<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('count_customers');
            $table->string('count_sales')->nullable();
            $table->unsignedInteger('production_count')->nullable();
            $table->dateTime('date');
            $table->string('amount_sales')->nullable();
            $table->string('monthly_income')->nullable();
            $table->string('current_cost')->nullable();
            $table->string('financial_cost')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
