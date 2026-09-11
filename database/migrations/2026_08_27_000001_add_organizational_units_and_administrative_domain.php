<?php

use Database\Seeders\OrganizationalAccessSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('submenu_panels')) {
            Schema::table('submenu_panels', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('personnel_code', 50)->unique();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('father_name', 120)->nullable();
            $table->string('birth_date', 20)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('mobile', 32)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('hire_date', 20)->nullable();
            $table->string('employment_type', 60)->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('insurance_number', 80)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index(['department', 'status']);
        });

        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('category', 40)->default('other')->index();
            $table->string('title');
            $table->string('disk', 60)->default('local');
            $table->text('path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('administrative_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_code', 80)->unique();
            $table->string('name');
            $table->string('category', 100)->nullable()->index();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 150)->nullable()->unique();
            $table->string('property_tag', 100)->nullable()->unique();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 40)->default('عدد');
            $table->string('acquisition_date', 20)->nullable();
            $table->decimal('purchase_cost', 20, 0)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('custodian_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('condition', 30)->default('good');
            $table->string('status', 30)->default('in_use')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['location', 'status']);
            $table->index(['custodian_employee_id', 'status']);
        });

        (new OrganizationalAccessSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_assets');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
    }
};
