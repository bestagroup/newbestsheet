<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('media_files', function (Blueprint $table) {
            $table->foreign('subject_id', 'media_files_subject_fk')
                ->references('id')->on('subject_files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropForeign('media_files_subject_fk');
        });
        Schema::dropIfExists('subject_files');
    }
};
