<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table): void {
            if (! Schema::hasColumn('kpis', 'deadline_at')) {
                $table->date('deadline_at')->nullable()->after('deadline')->index();
            }
            if (! Schema::hasColumn('kpis', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('time_step')->index();
            }
        });

        if (! Schema::hasTable('operational_notification_deliveries')) {
            Schema::create('operational_notification_deliveries', function (Blueprint $table): void {
                $table->id();
                $table->char('fingerprint', 64)->unique();
                $table->string('event_key', 120)->index();
                $table->string('trigger_key', 120)->nullable();
                $table->string('channel', 30)->index();
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recipient_phone', 32)->nullable();
                $table->string('related_type', 160)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('sent_at')->nullable()->index();
                $table->text('last_error')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['related_type', 'related_id'], 'ond_related_idx');
                $table->index(['event_key', 'channel', 'status'], 'ond_event_channel_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_notification_deliveries');

        // Intentionally non-destructive for KPI operational columns. They may contain production history.
    }
};
