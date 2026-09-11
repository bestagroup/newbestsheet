<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('company_name')->nullable();
            $table->string('commercial_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('national_id')->nullable();
            $table->string('economic_code')->nullable();
            $table->string('legal_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('ceo_name')->nullable();
            $table->string('ceo_national_code')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('company_id', 'projects_company_fk')
                ->references('id')->on('companies')->nullOnDelete();
        });

        Schema::table('media_files', function (Blueprint $table) {
            $table->foreign('company_id', 'media_files_company_fk')
                ->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropForeign('media_files_company_fk');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('projects_company_fk');
        });
        Schema::dropIfExists('companies');
    }
};
