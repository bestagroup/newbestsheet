<?php

namespace Tests\Feature;

use App\Events\CorrespondenceRefresh;
use App\Events\InvestmentStepTransitioned;
use App\Events\MessageSent;
use App\Models\Calendar;
use App\Models\Employee;
use App\Models\AdministrativeAsset;
use App\Models\ExternalLetter;
use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\MediaFile;
use App\Models\PortfolioMeeting;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\GoogleCalendarSyncService;
use App\Services\GovernanceService;
use App\Services\PortfolioFinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseIntegrationTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        config(['operations.notifications.sms_enabled' => false]);
        $this->admin = $this->user('superadmin');
        $this->actingAs($this->admin);
    }

    private function user(string $role): User
    {
        $user = User::query()->create(['name' => 'Test '.$role, 'email' => Str::uuid().'@example.test', 'password' => 'SafePassword123!', 'status' => 4, 'level' => 'admin', 'change_password' => 1]);
        $user->roles()->attach(Role::query()->where('title', $role)->firstOrFail());

        return $user;
    }

    private function project(int $step = 14): Project
    {
        return Project::query()->create(['title' => 'Test dossier', 'company_name' => 'Test company', 'invest_step' => $step]);
    }

    private function payment(Project $project): array
    {
        return ['project_id' => $project->id, 'amount' => '120000', 'serial' => 1, 'docserial' => 'DOC-'.Str::uuid(), 'date' => '1405/07/16', 'idempotency_key' => (string) Str::uuid()];
    }

    private function meetingData(Project $project): array
    {
        return ['project_id' => $project->id, 'title' => 'Annual assembly', 'type' => 'ordinary', 'held_on' => '1405/07/16', 'attendees' => 'Shareholder A; Director B', 'agenda' => ['Financial statements', 'Auditor selection']];
    }

    public function test_deleted_payment_key_cannot_recreate_payment(): void
    {
        $data = $this->payment($this->project());
        $finance = app(FinanceService::class)->create($data);
        app(FinanceService::class)->delete($finance->id);
        $this->postJson('/panel/finance', $data)->assertUnprocessable();
        $this->assertDatabaseCount('finances', 0);
    }

    public function test_statement_audit_failure_rolls_back_financial_change(): void
    {
        $project = $this->project();
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'insert into "business_audits"')) {
                throw new \RuntimeException('Audit unavailable');
            }
        });
        $this->postJson('/panel/financialstatement', ['project_id' => $project->id, 'year' => 1405, 'month' => 12, 'period_type' => 'annual', 'net_sales' => '9007199254740993'])->assertStatus(500);
        $this->assertDatabaseCount('financial_statements', 0);
    }

    public function test_referenced_attachment_cannot_be_deleted_or_moved(): void
    {
        Storage::fake('investment_documents');
        $project = $this->project();
        $this->post('/panel/filemanager', ['record_id' => $project->id, 'file' => UploadedFile::fake()->image('minutes.png')])->assertOk();
        $media = MediaFile::query()->firstOrFail();
        app(GovernanceService::class)->save([...$this->meetingData($project), 'media_file_id' => $media->id], $this->admin);
        $this->deleteJson('/panel/filemanager/'.$media->id)->assertUnprocessable();
        $this->patchJson('/panel/filemanager/'.$media->id, ['project_id' => $this->project()->id])->assertUnprocessable();
        $this->assertDatabaseHas('media_files', ['id' => $media->id, 'deleted_at' => null, 'project_id' => $project->id]);
    }

    public function test_standalone_attachment_owner_is_checked_when_linking(): void
    {
        Storage::fake('investment_documents');
        $this->post('/panel/filemanager', ['file' => UploadedFile::fake()->image('private.png')])->assertOk();
        $media = MediaFile::query()->firstOrFail();
        $other = $this->user('expert');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Services\EnterpriseRecordAccess::class)->assertAttachment($media->id, null, $other);
    }

    public function test_real_install_and_reference_data_are_idempotent(): void
    {
        $this->assertDatabaseCount('investsteps', 20);
        $count = DB::table('permission_role')->count();
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        $this->assertSame($count, DB::table('permission_role')->count());
        $this->assertDatabaseHas('permissions', ['slug' => 'meetings']);
        $this->assertDatabaseHas('permissions', ['slug' => 'letters']);
    }

    public function test_all_primary_screens_render_with_real_middleware(): void
    {
        foreach (['dashboard', 'panel/project', 'panel/company', 'panel/flow', 'panel/filemanager', 'correspondence', 'panel/calendar', 'panel/employees', 'panel/assets', 'panel/finance', 'panel/paidmanage', 'panel/financialstatement', 'panel/report', 'panel/meetings', 'panel/letters'] as $path) {
            $this->get('/'.$path)->assertOk();
        }
    }

    public function test_applicant_cannot_access_administration(): void
    {
        $this->actingAs($this->user('investee_representative'));
        foreach (['panel/employees', 'panel/assets', 'panel/finance', 'panel/financialstatement', 'panel/meetings', 'panel/letters'] as $path) {
            $this->get('/'.$path)->assertForbidden();
        }
    }

    public function test_registration_creates_dossier_and_twenty_stages(): void
    {
        auth()->logout();
        $this->post('/panel/fullregister', ['title' => 'New venture', 'CEO' => 'Founder', 'phone' => '09123456789', 'email' => 'founder@example.test', 'password' => 'StrongPass123!', 'password_confirmation' => 'StrongPass123!', 'terms_accepted' => 1])->assertRedirect(route('profile'));
        $project = Project::query()->firstOrFail();
        $this->assertCount(20, $project->stageInstances);
        $this->assertNotNull($project->company_id);
    }

    public function test_payment_replay_and_conflicting_replay_are_safe(): void
    {
        $data = $this->payment($this->project());
        $this->postJson('/panel/finance', $data)->assertOk();
        $this->postJson('/panel/paidmanage', $data)->assertOk();
        $this->assertDatabaseCount('finances', 1);
        $this->postJson('/panel/finance', [...$data, 'amount' => '130000'])->assertUnprocessable();
        $this->postJson('/panel/finance', [...$data, 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertDatabaseCount('finances', 1);
    }

    public function test_payment_validation_and_audit_survive_delete(): void
    {
        $data = $this->payment($this->project());
        foreach (['0', '-1', '1.23', '1e10'] as $bad) {
            $this->postJson('/panel/finance', [...$data, 'amount' => $bad])->assertUnprocessable();
        }
        $this->postJson('/panel/finance', $data)->assertOk();
        $finance = Finance::query()->firstOrFail();
        $this->patchJson('/panel/paidmanage/'.$finance->id, [...$data, 'amount' => '130000'])->assertOk();
        $this->deleteJson('/panel/finance/'.$finance->id)->assertOk();
        $this->assertDatabaseCount('finances', 0);
        $this->assertSame(3, DB::table('business_audits')->where('subject_type', Finance::class)->count());
        $this->get('/panel/finance/99999/edit')->assertNotFound();
    }

    public function test_financial_periods_coexist_but_duplicates_are_rejected(): void
    {
        $data = ['project_id' => $this->project()->id, 'year' => 1405, 'month' => 12, 'net_sales' => '9007199254740993'];
        $this->postJson('/panel/financialstatement', [...$data, 'period_type' => 'annual'])->assertOk();
        $this->postJson('/panel/financialstatement', [...$data, 'period_type' => 'quarterly'])->assertOk();
        $this->postJson('/panel/financialstatement', [...$data, 'period_type' => 'annual'])->assertUnprocessable();
        $this->postJson('/panel/financialstatement', [...$data, 'period_type' => 'quarterly', 'month' => 5])->assertUnprocessable();
        $this->assertDatabaseCount('financial_statements', 2);
        $this->assertSame('9007199254740993', (string) Financial_statement::query()->firstOrFail()->net_sales);
    }

    public function test_report_keeps_portfolio_without_statement(): void
    {
        $project = $this->project();
        app(FinanceService::class)->create($this->payment($project));
        $request = Request::create('/panel/report');
        $request->setUserResolver(fn () => $this->admin);
        $report = app(PortfolioFinancialReportService::class)->build($request);
        $this->assertCount(1, $report['portfolioRows']);
        $this->assertSame('120000', $report['totalPaid']);
    }

    public function test_project_payment_tranches_are_summed(): void
    {
        $project = $this->project();
        app(FinanceService::class)->create($this->payment($project));
        app(FinanceService::class)->create($this->payment($project));
        $this->getJson('/panel/project', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('data.0.first_stage_payment', '240,000');
    }

    public function test_unassigned_reviewer_cannot_mutate_another_dossier_or_minute(): void
    {
        $project = $this->project();
        $this->postJson('/minute', ['project_id' => $project->id, 'title' => 'Private minute'])->assertOk();
        $minute = DB::table('minutes')->first();
        $reviewer = $this->user('expert');
        // Grant menu permissions deliberately: record-level access must still deny.
        $role = $reviewer->roles()->first();
        foreach (['project', 'flow'] as $slug) {
            $role->permissions()->syncWithoutDetaching([DB::table('permissions')->where('slug', $slug)->value('id') => ['can_view' => true, 'can_edit' => true, 'can_delete' => true]]);
        }
        $this->actingAs($reviewer);
        $this->patchJson('/panel/project/'.$project->id, ['title' => 'Attack'])->assertForbidden();
        $this->deleteJson('/panel/project/'.$project->id, ['id' => $project->id])->assertForbidden();
        $this->getJson('/minute/'.$minute->id.'/edit')->assertForbidden();
        $this->deleteJson('/minute/'.$minute->id)->assertForbidden();
    }

    public function test_meeting_finalization_requires_every_agenda_item(): void
    {
        $this->post('/panel/meetings', $this->meetingData($this->project()))->assertRedirect();
        $meeting = PortfolioMeeting::query()->firstOrFail();
        $this->get('/panel/meetings/'.$meeting->id)->assertOk();
        $this->postJson('/panel/meetings/'.$meeting->id.'/resolutions', ['agenda_index' => 7, 'body' => 'Invalid', 'assigned_to' => $this->admin->id])->assertUnprocessable();
        $this->post('/panel/meetings/'.$meeting->id.'/transition', ['status' => 'held', 'lock_version' => $meeting->lock_version])->assertRedirect();
        $meeting->refresh();
        $this->postJson('/panel/meetings/'.$meeting->id.'/transition', ['status' => 'finalized', 'lock_version' => $meeting->lock_version])->assertUnprocessable();
        foreach ([0, 1] as $index) {
            $this->post('/panel/meetings/'.$meeting->id.'/resolutions', ['agenda_index' => $index, 'body' => 'Approved item '.$index, 'assigned_to' => $this->admin->id, 'due_on' => '2026-11-01'])->assertRedirect();
        }
        $this->post('/panel/meetings/'.$meeting->id.'/transition', ['status' => 'finalized', 'lock_version' => $meeting->fresh()->lock_version])->assertRedirect();
        $this->postJson('/panel/meetings/'.$meeting->id.'/resolutions', ['agenda_index' => 0, 'body' => 'Late edit', 'assigned_to' => $this->admin->id])->assertConflict();
        $resolution = $meeting->resolutions()->firstOrFail();
        $this->patch('/panel/meetings/'.$meeting->id.'/resolutions/'.$resolution->id, ['completion_note' => 'Executed with evidence'])->assertRedirect();
        $this->assertSame('completed', $resolution->fresh()->status);
    }

    public function test_meeting_optimistic_lock_rejects_stale_edit(): void
    {
        $data = $this->meetingData($this->project());
        $meeting = app(GovernanceService::class)->save($data, $this->admin);
        $this->patchJson('/panel/meetings/'.$meeting->id, [...$data, 'lock_version' => 0])->assertConflict();
        $this->patch('/panel/meetings/'.$meeting->id, [...$data, 'lock_version' => 1])->assertRedirect();
    }

    public function test_external_letter_is_private_and_closed_record_is_locked(): void
    {
        $other = $this->user('administrative_support_management');
        $this->post('/panel/letters', ['direction' => 'incoming', 'correspondent' => 'External company', 'subject' => 'Confidential document', 'body' => 'Private details', 'issued_on' => '1405/07/16', 'assigned_to' => $this->admin->id, 'confidential' => true])->assertRedirect();
        $letter = ExternalLetter::query()->firstOrFail();
        $this->get('/panel/letters/'.$letter->id)->assertOk();
        $this->actingAs($other)->get('/panel/letters/'.$letter->id)->assertNotFound();
        $this->actingAs($this->admin);
        $this->postJson('/panel/letters/'.$letter->id.'/transition', ['status' => 'closed', 'lock_version' => 1])->assertUnprocessable();
        $this->post('/panel/letters/'.$letter->id.'/transition', ['status' => 'closed', 'lock_version' => 1, 'completion_note' => 'Response registered'])->assertRedirect();
        $this->postJson('/panel/letters/'.$letter->id.'/transition', ['status' => 'in_progress', 'lock_version' => 2])->assertConflict();
    }

    public function test_private_upload_download_and_soft_delete_retain_bytes(): void
    {
        Storage::fake('investment_documents');
        $project = $this->project();
        $this->post('/panel/filemanager', ['record_id' => $project->id, 'file' => UploadedFile::fake()->image('evidence.png')])->assertOk();
        $media = MediaFile::query()->firstOrFail();
        $this->get('/panel/media/'.$media->id.'/download')->assertOk();
        $this->actingAs($this->user('investee_representative'))->get('/panel/media/'.$media->id.'/download')->assertForbidden();
        $this->actingAs($this->admin)->delete('/panel/filemanager/'.$media->id)->assertOk();
        $this->assertSoftDeleted('media_files', ['id' => $media->id]);
        Storage::disk('investment_documents')->assertExists($media->file_path);
        $this->get('/panel/media/'.$media->id.'/download')->assertNotFound();
    }

    public function test_calendar_invalid_range_and_non_owner_mutation_are_rejected(): void
    {
        $this->getJson('/panel/calendar/events?start=invalid')->assertUnprocessable();
        $calendar = Calendar::query()->create(['created_by' => $this->admin->id, 'title' => 'Private event', 'start' => '1405-07-16 09:00:00', 'end' => '1405-07-16 10:00:00', 'guests' => []]);
        $other = $this->user('expert');
        $permission = DB::table('permissions')->where('slug', 'calendar')->first();
        if (! $permission) {
            $id = DB::table('permissions')->insertGetId(['slug' => 'calendar', 'title' => 'calendar', 'label' => 'Calendar', 'user_id' => $this->admin->id]);
        } else {
            $id = $permission->id;
        }
        $other->roles()->first()->permissions()->syncWithoutDetaching([$id => ['can_view' => true, 'can_edit' => true, 'can_delete' => true]]);
        $this->actingAs($other)->deleteJson('/panel/calendar/delete/'.$calendar->id)->assertForbidden();
    }

    public function test_workflow_requires_reviewed_documents_and_prevents_duplicate_decision(): void
    {
        Event::fake([InvestmentStepTransitioned::class]);
        $project = $this->project(1);
        DB::table('invest_step_document_requirements')->where('invest_step_id', 1)->update(['is_required' => true]);
        $data = ['project_id' => $project->id, 'step_id' => 1, 'status' => 'approved'];
        $this->postJson('/panel/flow', $data)->assertUnprocessable();
        $requirements = DB::table('invest_step_document_requirements')->where('invest_step_id', 1)->where('is_required', true)->get();
        $this->assertNotEmpty($requirements);
        foreach ($requirements as $requirement) {
            for ($i = 0; $i < $requirement->minimum_files; $i++) {
                MediaFile::query()->create(['name' => Str::uuid().'.pdf', 'file_path' => 'test.pdf', 'project_id' => $project->id, 'subject_id' => $requirement->subject_file_id, 'document_requirement_id' => $requirement->id, 'scan_status' => 'clean', 'status' => 0, 'user_id' => $this->admin->id]);
            }
        }
        $this->postJson('/panel/flow', $data)->assertUnprocessable();
        MediaFile::query()->update(['status' => 4]);
        $this->postJson('/panel/flow', $data)->assertOk();
        $this->assertSame(2, (int) $project->fresh()->invest_step);
        $this->postJson('/panel/flow', $data)->assertUnprocessable();
        $this->assertDatabaseCount('project_steps', 1);
    }

    public function test_staff_asset_custody_blocks_premature_employee_archival(): void
    {
        $this->post('/panel/employees', ['personnel_code' => 'P001', 'first_name' => 'Test', 'last_name' => 'Employee', 'status' => 'active'])->assertRedirect();
        $employee = Employee::query()->firstOrFail();
        $this->post('/panel/assets', ['asset_code' => 'LAP001', 'name' => 'Laptop', 'quantity' => 1, 'unit' => 'عدد', 'custodian_employee_id' => $employee->id, 'condition' => 'good', 'status' => 'assigned'])->assertRedirect();
        $this->deleteJson('/panel/employees/'.$employee->id)->assertConflict();
        $asset = AdministrativeAsset::query()->firstOrFail();
        $this->delete('/panel/assets/'.$asset->id)->assertRedirect();
        $this->delete('/panel/employees/'.$employee->id)->assertRedirect();
        $this->assertSoftDeleted($employee);
        $this->postJson('/panel/assets', ['asset_code' => 'LAP002', 'name' => 'Laptop', 'quantity' => 1, 'unit' => 'عدد', 'custodian_employee_id' => $employee->id, 'condition' => 'good', 'status' => 'assigned'])->assertUnprocessable();
    }

    public function test_internal_correspondence_membership_and_script_encoding(): void
    {
        Event::fake([MessageSent::class, CorrespondenceRefresh::class]);
        $recipient = $this->user('expert');
        $outsider = $this->user('expert');
        $payload = '</script><script>alert(1)</script>';
        $response = $this->postJson('/correspondence', ['subject' => $payload, 'body' => $payload, 'recipients' => [$recipient->id]])->assertCreated();
        $id = $response->json('conversation_id');
        $html = $this->get('/correspondence')->assertOk()->getContent();
        $this->assertStringNotContainsString($payload, $html);
        $this->actingAs($outsider)->postJson('/correspondence/'.$id.'/read')->assertNotFound();
        $this->postJson('/correspondence', ['conversation_id' => $id, 'body' => 'Unauthorized reply'])->assertNotFound();
        $this->actingAs($recipient)->postJson('/correspondence/'.$id.'/read')->assertOk();
        $this->assertDatabaseHas('conversation_user', ['conversation_id' => $id, 'user_id' => $recipient->id, 'unread_count' => 0]);
    }

    public function test_standalone_archive_is_visible_and_recoverable(): void
    {
        Storage::fake('investment_documents');
        $this->post('/panel/filemanager', ['file' => UploadedFile::fake()->image('standalone.png')])->assertOk();
        $media = MediaFile::query()->firstOrFail();
        $this->getJson('/panel/filemanager', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('data.0.id', $media->id);
        $this->delete('/panel/filemanager/'.$media->id)->assertOk();
        $this->get('/panel/archive-trash')->assertOk();
        $this->post('/panel/archive-trash/'.$media->id.'/restore')->assertRedirect();
        $this->get('/panel/media/'.$media->id.'/download')->assertOk();
    }

    public function test_archived_financial_history_cannot_be_cascade_deleted_via_flow(): void
    {
        $project = $this->project();
        app(FinanceService::class)->create($this->payment($project));
        $this->deleteJson('/panel/flow/'.$project->id)->assertConflict();
        $this->deleteJson('/panel/project/'.$project->id)->assertConflict();
        $this->assertDatabaseCount('finances', 1);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_delegated_user_manager_cannot_promote_to_superadmin(): void
    {
        $manager = $this->user('administrative_support_management');
        $id = DB::table('permissions')->insertGetId(['title' => 'paneluser', 'label' => 'Users', 'slug' => 'paneluser']);
        $manager->roles()->first()->permissions()->syncWithoutDetaching([$id => ['can_view' => true, 'can_insert' => true, 'can_edit' => true, 'can_delete' => true]]);
        $this->actingAs($manager)->postJson('/panel/paneluser', ['typeuser_id' => Role::query()->where('title', 'superadmin')->value('id'), 'name' => 'Escalation', 'email' => 'escalation@example.test', 'password' => 'SafePassword123!', 'password_confirmation' => 'SafePassword123!'])->assertForbidden();
        $this->deleteJson('/panel/paneluser/'.$this->admin->id)->assertForbidden();
    }

    public function test_antivirus_failure_keeps_file_quarantined(): void
    {
        Storage::fake('investment_documents');
        config(['investment.documents.antivirus_enabled' => true, 'investment.documents.scanner' => '/nonexistent-scanner']);
        $this->post('/panel/filemanager', ['file' => UploadedFile::fake()->image('scan.png')])->assertOk();
        $media = MediaFile::query()->firstOrFail();
        $this->get('/panel/media/'.$media->id.'/download')->assertStatus(423);
        $this->artisan('documents:scan')->assertExitCode(1);
        $this->assertSame('pending', $media->fresh()->scan_status);
    }

    public function test_calendar_creation_and_visibility_use_real_validation(): void
    {
        $this->mock(GoogleCalendarSyncService::class)->shouldReceive('sync')->once();
        $this->postJson('/panel/calendar/store', ['eventTitle' => 'Board calendar', 'eventStartDate' => '1405/07/16 09:00', 'eventEndDate' => '1405/07/16 10:00', 'eventGuests' => []])->assertOk();
        $this->getJson('/panel/calendar/events')->assertOk()->assertJsonCount(1);
        $this->postJson('/panel/calendar/store', ['eventTitle' => 'Invalid time', 'eventStartDate' => '1405/07/16 10:00', 'eventEndDate' => '1405/07/16 09:00'])->assertUnprocessable();
    }

    public function test_report_summary_uses_business_stage_boundaries_and_latest_profit(): void
    {
        $service = app(\App\Services\InvestmentReportSummaryService::class);
        $before = $service->build($this->admin, 'annual')['counts'];
        foreach ([5, 6, 13, 20] as $step) { $this->project($step); }
        $rejected = $this->project(18);
        $rejected->update(['is_rejected' => 1, 'amount_request_accept' => '99999']);
        $a = $this->project(14);
        $a->update(['amount_request_accept' => '1000']);
        $b = $this->project(19);
        $b->update(['amount_request_accept' => '2000']);
        $this->project(17); // No statement: must count as missing, not zero profit.
        Finance::query()->create(array_replace($this->payment($a), ['amount' => '3500']));
        foreach ([[$a, 1404, '900'], [$a, 1405, '-100'], [$b, 1404, '300']] as [$p, $year, $profit]) {
            Financial_statement::query()->create(['project_id' => $p->id, 'period_type' => 'annual', 'year' => $year, 'month' => 12, 'net_profit' => $profit]);
        }
        $result = $service->build($this->admin, 'annual');
        $this->assertSame($before['projects'] + 8, $result['counts']['projects']);
        $this->assertSame($before['active'] + 1, $result['counts']['active']);
        $this->assertSame($before['rejected'] + 1, $result['counts']['rejected']);
        $this->assertSame($before['portfolio'] + 3, $result['counts']['portfolio']);
        $this->assertSame('3000', $result['contract']);
        $this->assertSame('3500', $result['paid']);
        $this->assertSame('-500', $result['remaining']);
        $this->assertSame('200', $result['profit']);
        $this->assertSame(2, $result['reported_companies']);
        $this->assertSame(1, $result['missing_companies']);
        $this->assertTrue($result['mixed_periods']);
        $this->assertNull($service->build($this->admin, 'quarterly')['profit']);
    }

    public function test_report_financial_charts_render_filtered_periods_and_exclude_inactive_portfolio(): void
    {
        $active = $this->project(14);
        $exited = $this->project(20);
        $rejected = $this->project(16);
        $rejected->update(['is_rejected' => 1]);
        foreach ([$active, $exited, $rejected] as $project) {
            foreach ([1404, 1405] as $year) {
                Financial_statement::query()->create(['project_id'=>$project->id, 'period_type'=>'annual', 'year'=>$year, 'month'=>12, 'net_sales'=>'100', 'net_profit'=>'-20']);
            }
        }
        $this->get('/panel/report?'.http_build_query(['period_type'=>'annual','from_date'=>'۱۴۰۵/۰۱/۰۱']))
            ->assertOk()->assertSee('گزارش مالی شرکت‌ها برای مدیرعامل و هیئت‌مدیره')->assertSee('شرکت‌ها برای مقایسه')
            ->assertViewHas('financialCharts', fn ($data) => $data['expected'] === 1
                && count($data['periods']) === 1 && $data['periods'][0]['period'] === '1405/12'
                && $data['periods'][0]['profit'] === '-20');
    }


    public function test_board_comparison_aligns_three_companies_and_all_periods_without_mixing_types(): void
    {
        $a=$this->project(14);$b=$this->project(15);$c=$this->project(19);
        foreach ([[$a,1398,3,'quarterly','100'],[$a,1405,6,'quarterly','200'],[$b,1405,3,'quarterly','300'],[$c,1405,6,'quarterly','-50'],[$a,1405,6,'annual','999']] as [$p,$year,$month,$type,$value]) {
            Financial_statement::query()->create(['project_id'=>$p->id,'year'=>$year,'month'=>$month,'period_type'=>$type,'net_sales'=>$value,'net_profit'=>$value]);
        }
        $this->get('/panel/report?'.http_build_query(['chart_companies'=>['project:'.$a->id,'project:'.$b->id,'project:'.$c->id]]))->assertOk()
            ->assertDontSee('name="chart_period"',false)->assertDontSee('name="income_basis"',false)
            ->assertViewHas('boardCharts', function ($data) {
                $quarter=collect($data['charts'])->firstWhere('id','quarterly-sales');
                $annual=collect($data['charts'])->firstWhere('id','annual-sales');
                return count($data['selectedCompanies'])===3 && $quarter['labels']===['1398/03','1405/03','1405/06']
                    && $quarter['series'][0]['values']===['100',null,'200']
                    && $quarter['series'][1]['values']===[null,'300',null]
                    && $quarter['series'][2]['values']===[null,null,'-50']
                    && $annual['series'][0]['values']===['999'];
            });
    }

    public function test_board_comparison_preserves_exact_values_and_rejects_any_inactive_selection(): void
    {
        $a=$this->project(14);$exited=$this->project(20);
        Financial_statement::query()->create(['project_id'=>$a->id,'year'=>1405,'month'=>12,'period_type'=>'annual','net_sales'=>'9007199254740993','net_profit'=>'0','total_current_liabilities'=>'0']);
        $this->get('/panel/report?'.http_build_query(['chart_companies'=>['project:'.$a->id,'project:'.$a->id]]))->assertOk()
            ->assertViewHas('boardCharts', function ($data) {
                return count($data['selectedCompanies'])===1
                    && collect($data['charts'])->firstWhere('id','annual-sales')['series'][0]['values']===['9007199254740993']
                    && collect($data['charts'])->firstWhere('id','annual-profit')['series'][0]['values']===['0']
                    && collect($data['charts'])->firstWhere('id','annual-current')['series'][0]['values']===[null];
            });
        $this->get('/panel/report?'.http_build_query(['chart_companies'=>['project:'.$a->id,'project:'.$exited->id]]))->assertNotFound();
    }
}
