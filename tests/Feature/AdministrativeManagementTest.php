<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckAdminAuthenticated;
use App\Http\Middleware\CheckResourcePermission;
use App\Models\AdministrativeAsset;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdministrativeManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            CheckAdminAuthenticated::class,
            CheckResourcePermission::class,
        ]);

        Schema::dropIfExists('administrative_assets');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('personnel_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->nullable()->unique();
            $table->string('father_name')->nullable();
            $table->string('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('mobile')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('address')->nullable();
            $table->string('hire_date')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('insurance_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('administrative_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->string('property_tag')->nullable()->unique();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit')->default('عدد');
            $table->string('acquisition_date')->nullable();
            $table->decimal('purchase_cost', 20, 0)->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('custodian_employee_id')->nullable();
            $table->string('condition')->default('good');
            $table->string('status')->default('in_use');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_employee_and_asset_records_can_be_created_and_archived(): void
    {
        $user = User::query()->create([
            'name' => 'مدیر اداری',
            'email' => 'admin-support@example.test',
            'password' => 'password',
        ]);
        $activity = $this->mock(ActivityLogService::class);
        $activity->shouldReceive('record')->times(4);
        $this->actingAs($user);

        $this->post('/panel/employees', [
            'personnel_code' => 'P-100',
            'first_name' => 'علی',
            'last_name' => 'احمدی',
            'national_id' => '0012345678',
            'status' => 'active',
        ])->assertRedirect(route('employees.index'));

        $employee = Employee::query()->firstOrFail();
        $this->assertSame('علی احمدی', $employee->full_name);

        $this->post('/panel/assets', [
            'asset_code' => 'A-100',
            'name' => 'رایانه همراه',
            'quantity' => '۱',
            'unit' => 'عدد',
            'purchase_cost' => '۱,۲۵۰,۰۰۰',
            'custodian_employee_id' => $employee->id,
            'condition' => 'good',
            'status' => 'assigned',
        ])->assertRedirect(route('assets.index'));

        $asset = AdministrativeAsset::query()->firstOrFail();
        $this->assertSame(1250000, (int) $asset->getRawOriginal('purchase_cost'));
        $this->assertSame($employee->id, $asset->custodian_employee_id);

        $this->delete('/panel/assets/'.$asset->id)->assertRedirect(route('assets.index'));
        $this->assertSoftDeleted('administrative_assets', ['id' => $asset->id]);

        $this->delete('/panel/employees/'.$employee->id)->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }
}
