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
        Schema::create('project_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->comment('طرح ');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->string('title')->comment('عنوان');
            $table->integer('step_number')->comment('شماره گام');
            $table->text('description')->comment('توضیحات');
            $table->string('status')->comment('وضعیت');
            $table->unsignedBigInteger('user_id')->nullable()->comment('کارشناس ');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_steps');
    }
};
