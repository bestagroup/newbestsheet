<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->string('original_name')->nullable();
            $table->string('type')->nullable()->index();
            $table->string('mime')->nullable();
            $table->text('file_path')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            $table->unsignedSmallInteger('role')->nullable()->index();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->timestamps();

            $table->index(['project_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
