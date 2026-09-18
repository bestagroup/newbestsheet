<?php

namespace Tests\Feature;

use App\Enums\InvestmentRole;
use App\Models\MediaFile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_resolves_a_legacy_public_path_with_storage_prefix(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/reports/quarterly.pdf', 'pdf-content');

        $media = MediaFile::query()->forceCreate([
            'name' => 'quarterly.pdf',
            'original_name' => 'quarterly.pdf',
            'file_path' => 'storage/uploads/reports/quarterly.pdf',
            'disk' => 'legacy_public_disk',
            'mime' => 'application/pdf',
            'type' => 'document',
            'size' => 11,
            'scan_status' => 'legacy',
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('media.download', $media))
            ->assertOk()
            ->assertDownload('quarterly.pdf');
    }

    public function test_download_uses_the_recorded_private_disk_for_current_documents(): void
    {
        Storage::fake('investment_documents');
        Storage::disk('investment_documents')->put('42/unassigned/documents/report.pdf', 'private-content');

        $media = MediaFile::query()->forceCreate([
            'name' => 'report.pdf',
            'original_name' => 'report.pdf',
            'file_path' => '42/unassigned/documents/report.pdf',
            'disk' => 'investment_documents',
            'mime' => 'application/pdf',
            'type' => 'document',
            'size' => 15,
            'scan_status' => 'clean',
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('media.download', $media))
            ->assertOk()
            ->assertDownload('report.pdf');
    }

    private function superAdmin(): User
    {
        $role = Role::query()->where('title', InvestmentRole::SuperAdmin->value)->firstOrFail();
        $user = User::query()->create([
            'name' => 'مدیر سامانه',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'level' => 'admin',
            'status' => 4,
            'change_password' => 1,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }
}
