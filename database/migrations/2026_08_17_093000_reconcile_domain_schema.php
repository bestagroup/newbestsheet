<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reconcileUsers();
        $this->reconcileLocationTables();
        $this->reconcileProjects();
        $this->reconcileCompanies();
        $this->reconcileMediaFiles();
        $this->reconcileMinutes();
        $this->reconcileFinancialStatements();
        $this->reconcileKpis();
        $this->reconcileComments();
        $this->reconcileMenus();
        $this->reconcileFinances();
        $this->reconcileConversationPivot();
    }

    public function down(): void
    {
        // This migration is an idempotent compatibility bridge for legacy databases.
        // Rollback is intentionally non-destructive to avoid dropping pre-existing data.
    }

    private function reconcileUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'google_expires_in')) {
            Schema::table('users', fn (Blueprint $table) => $table->unsignedInteger('google_expires_in')->nullable());
        }
    }

    private function reconcileLocationTables(): void
    {
        foreach (['states', 'cities'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'created_at')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
            }

            if (! Schema::hasColumn($tableName, 'updated_at')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
            }
        }
    }

    private function reconcileProjects(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        $columns = [
            'user_id' => fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->index(),
            'company_id' => fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable()->index(),
            'registration_number' => fn (Blueprint $table) => $table->string('registration_number')->nullable(),
            'registration_date' => fn (Blueprint $table) => $table->string('registration_date')->nullable(),
            'national_id' => fn (Blueprint $table) => $table->string('national_id')->nullable(),
            'economic_code' => fn (Blueprint $table) => $table->string('economic_code')->nullable(),
            'legal_type' => fn (Blueprint $table) => $table->string('legal_type')->nullable(),
            'tel' => fn (Blueprint $table) => $table->string('tel')->nullable(),
            'email' => fn (Blueprint $table) => $table->string('email')->nullable(),
            'website' => fn (Blueprint $table) => $table->string('website')->nullable(),
            'postal_code' => fn (Blueprint $table) => $table->string('postal_code', 20)->nullable(),
            'state' => fn (Blueprint $table) => $table->unsignedBigInteger('state')->nullable()->index(),
            'city' => fn (Blueprint $table) => $table->unsignedBigInteger('city')->nullable()->index(),
            'ceo_national_code' => fn (Blueprint $table) => $table->string('ceo_national_code')->nullable(),
            'ceo_phone' => fn (Blueprint $table) => $table->string('ceo_phone')->nullable(),
            'address' => fn (Blueprint $table) => $table->text('address')->nullable(),
            'logo' => fn (Blueprint $table) => $table->string('logo')->nullable(),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable(),
            'percentageshare' => fn (Blueprint $table) => $table->decimal('percentageshare', 8, 4)->nullable(),
            'invest_step' => fn (Blueprint $table) => $table->unsignedSmallInteger('invest_step')->default(1)->index(),
            'is_rejected' => fn (Blueprint $table) => $table->boolean('is_rejected')->default(false)->index(),
            'reject_step' => fn (Blueprint $table) => $table->unsignedSmallInteger('reject_step')->nullable()->index(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('projects', $column)) {
                Schema::table('projects', $definition);
            }
        }
    }

    private function reconcileCompanies(): void
    {
        if (Schema::hasTable('companies') && ! Schema::hasColumn('companies', 'title')) {
            Schema::table('companies', fn (Blueprint $table) => $table->string('title')->nullable());
        }
    }

    private function reconcileMediaFiles(): void
    {
        if (! Schema::hasTable('media_files')) {
            return;
        }

        $columns = [
            'project_id' => fn (Blueprint $table) => $table->unsignedBigInteger('project_id')->nullable()->index(),
            'company_id' => fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable()->index(),
            'subject_id' => fn (Blueprint $table) => $table->unsignedBigInteger('subject_id')->nullable()->index(),
            'mime' => fn (Blueprint $table) => $table->string('mime')->nullable(),
            'role' => fn (Blueprint $table) => $table->unsignedSmallInteger('role')->nullable()->index(),
            'status' => fn (Blueprint $table) => $table->unsignedTinyInteger('status')->default(0)->index(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('media_files', $column)) {
                Schema::table('media_files', $definition);
            }
        }
    }

    private function reconcileMinutes(): void
    {
        if (Schema::hasTable('minutes') && ! Schema::hasColumn('minutes', 'company_id')) {
            Schema::table('minutes', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable()->index());
        }
    }

    private function reconcileFinancialStatements(): void
    {
        if (! Schema::hasTable('financial_statements')) {
            return;
        }

        if (! Schema::hasColumn('financial_statements', 'year')) {
            Schema::table('financial_statements', fn (Blueprint $table) => $table->unsignedSmallInteger('year')->nullable()->index());
        }

        if (! Schema::hasColumn('financial_statements', 'month')) {
            Schema::table('financial_statements', fn (Blueprint $table) => $table->unsignedTinyInteger('month')->nullable()->index());
        }
    }

    private function reconcileKpis(): void
    {
        if (! Schema::hasTable('kpis')) {
            return;
        }

        if (Schema::hasColumn('kpis', 'factor_number') && ! Schema::hasColumn('kpis', 'kpi_number')) {
            Schema::table('kpis', fn (Blueprint $table) => $table->renameColumn('factor_number', 'kpi_number'));
        }
    }

    private function reconcileComments(): void
    {
        if (! Schema::hasTable('comments')) {
            return;
        }

        if (! Schema::hasColumn('comments', 'name')) {
            Schema::table('comments', fn (Blueprint $table) => $table->string('name')->nullable());
        }

        if (! Schema::hasColumn('comments', 'phone')) {
            Schema::table('comments', fn (Blueprint $table) => $table->string('phone', 20)->nullable());
        }
    }

    private function reconcileMenus(): void
    {
        if (Schema::hasTable('menus')) {
            if (! Schema::hasColumn('menus', 'label')) {
                Schema::table('menus', fn (Blueprint $table) => $table->string('label')->nullable());
            }
            if (! Schema::hasColumn('menus', 'icon')) {
                Schema::table('menus', fn (Blueprint $table) => $table->string('icon')->nullable());
            }
        }

        if (Schema::hasTable('submenus') && ! Schema::hasColumn('submenus', 'label')) {
            Schema::table('submenus', fn (Blueprint $table) => $table->string('label')->nullable());
        }
    }

    private function reconcileFinances(): void
    {
        if (! Schema::hasTable('finances')) {
            return;
        }

        if (! Schema::hasColumn('finances', 'docserial')) {
            Schema::table('finances', fn (Blueprint $table) => $table->string('docserial')->nullable());
        }

        if (! Schema::hasColumn('finances', 'finance_type')) {
            Schema::table('finances', fn (Blueprint $table) => $table->string('finance_type')->nullable()->index());
        }
    }

    private function reconcileConversationPivot(): void
    {
        if (Schema::hasTable('conversation_users') && ! Schema::hasTable('conversation_user')) {
            Schema::rename('conversation_users', 'conversation_user');
        }
    }
};
