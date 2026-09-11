<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('company_id')->nullable();

            $table->string('title');
            $table->string('company_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('registration_date')->nullable();
            $table->string('national_id')->nullable();
            $table->string('economic_code')->nullable();
            $table->string('legal_type')->nullable();
            $table->string('tel')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->foreignId('state')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('city')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('CEO')->nullable();
            $table->string('ceo_national_code')->nullable();
            $table->string('ceo_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->decimal('percentageshare', 8, 4)->nullable();

            $table->string('portfo_status')->nullable();
            $table->string('flow_level')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->string('activity_status')->nullable();
            $table->date('start_date')->nullable();

            $table->decimal('amount_request_accept', 20, 0)->nullable();
            $table->decimal('amount_deposited', 20, 0)->nullable();
            $table->decimal('amount_commitment_first_stage', 20, 0)->nullable();
            $table->decimal('first_stage_payment', 20, 0)->nullable();
            $table->decimal('amount_commitment_second_stage', 20, 0)->nullable();
            $table->decimal('second_stage_payment', 20, 0)->nullable();
            $table->decimal('amount_commitment_third_stage', 20, 0)->nullable();
            $table->decimal('third_stage_payment', 20, 0)->nullable();
            $table->decimal('amount_commitment_fourth_stage', 20, 0)->nullable();
            $table->decimal('fourth_stage_payment', 20, 0)->nullable();
            $table->decimal('amount_commitment_fifth_stage', 20, 0)->nullable();
            $table->decimal('fifth_stage_payment', 20, 0)->nullable();
            $table->decimal('commitment_balance', 20, 0)->nullable();

            $table->unsignedSmallInteger('invest_step')->default(1)->index();
            $table->boolean('is_rejected')->default(false)->index();
            $table->unsignedSmallInteger('reject_step')->nullable()->index();

            $table->timestamps();

            $table->index(['user_id', 'invest_step']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
