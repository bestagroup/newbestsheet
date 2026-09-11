<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_user_read_idx');
            });
        }

        Schema::table('calendars', function (Blueprint $table) {
            if (! Schema::hasColumn('calendars', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('calendars', 'google_event_id')) {
                $table->string('google_event_id')->nullable()->after('guests')->index();
            }
            if (! Schema::hasColumn('calendars', 'google_sync_status')) {
                $table->string('google_sync_status', 30)->default('not_synced')->after('google_event_id')->index();
            }
            if (! Schema::hasColumn('calendars', 'google_sync_error')) {
                $table->text('google_sync_error')->nullable()->after('google_sync_status');
            }
        });

        Schema::table('minutes', function (Blueprint $table) {
            if (! Schema::hasColumn('minutes', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('user_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'user_logs_action_created_idx');
            $table->index('created_at', 'user_logs_created_at_idx');
        });

        Schema::table('message_recipients', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'msg_recipient_user_read_idx');
        });

        Schema::table('calendars', function (Blueprint $table) {
            $table->index('start', 'calendars_start_idx');
        });
    }

    public function down(): void
    {
        // Compatibility migration: keep additive operational metadata on rollback.
    }
};
