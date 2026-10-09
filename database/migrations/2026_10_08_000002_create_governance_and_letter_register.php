<?php

use Database\Seeders\EnterpriseModuleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_meetings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $t->string('title');
            $t->string('type', 40);
            $t->string('held_on', 10);
            $t->string('location')->nullable();
            $t->json('agenda');
            $t->text('attendees');
            $t->string('status', 30)->default('draft')->index();
            $t->foreignId('media_file_id')->nullable()->constrained('media_files')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(0);
            $t->timestamps();
            $t->index(['project_id', 'held_on']);
        });
        Schema::create('meeting_resolutions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('portfolio_meeting_id')->constrained('portfolio_meetings')->restrictOnDelete();
            $t->unsignedInteger('agenda_index');
            $t->text('body');
            $t->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $t->date('due_on')->nullable();
            $t->string('status', 30)->default('pending');
            $t->text('completion_note')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'due_on']);
        });
        Schema::create('external_letters', function (Blueprint $t): void {
            $t->id();
            $t->string('direction', 20);
            $t->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $t->string('external_reference')->nullable();
            $t->string('correspondent');
            $t->string('subject');
            $t->text('body');
            $t->string('issued_on', 10);
            $t->date('due_on')->nullable();
            $t->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $t->boolean('confidential')->default(false);
            $t->string('status', 30)->default('registered')->index();
            $t->text('completion_note')->nullable();
            $t->foreignId('media_file_id')->nullable()->constrained('media_files')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(0);
            $t->timestamps();
            $t->index(['assigned_to', 'status', 'due_on']);
        });
        (new EnterpriseModuleSeeder)->run();
    }

    public function down(): void
    {
        throw new RuntimeException('Business registers are retained. Restore a backup to reverse this release.');
    }
};
