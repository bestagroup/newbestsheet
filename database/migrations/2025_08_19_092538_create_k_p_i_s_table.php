<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('type_value')->nullable();
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->string('deadline')->nullable();
            $table->string('period_time')->nullable();
            $table->string('time_step')->nullable();
            $table->unsignedInteger('kpi_number')->index();
            $table->string('file_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
