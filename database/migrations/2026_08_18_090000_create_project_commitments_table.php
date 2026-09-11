<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_commitments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('commitment_id')->constrained('commitments')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('due_date', 20)->nullable()->comment('تاریخ سررسید نمایشی/جلالی');
            $table->date('due_at')->nullable()->index();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'commitment_id'], 'pc_project_commitment_unique');
            $table->index(['project_id', 'status', 'due_at'], 'pc_project_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_commitments');
    }
};
