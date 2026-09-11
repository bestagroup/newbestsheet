<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invest_step_document_requirements')) {
            return;
        }

        Schema::create('invest_step_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invest_step_id')->constrained('investsteps')->cascadeOnDelete();
            $table->foreignId('subject_file_id')->constrained('subject_files')->cascadeOnDelete();
            $table->boolean('is_required')->default(false)->index();
            $table->unsignedTinyInteger('minimum_files')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['invest_step_id', 'subject_file_id'], 'isdr_step_subject_uq');
            $table->index(['invest_step_id', 'is_required'], 'isdr_step_required_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invest_step_document_requirements');
    }
};
