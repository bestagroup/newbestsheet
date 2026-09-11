<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class InvesteePortalService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function assertInvestee(User $user): void
    {
        if (! $user->hasRole(InvestmentRole::InvesteeRepresentative->value) && $user->level !== 'applicant') {
            throw new AuthorizationException('این بخش فقط برای نماینده سرمایه‌پذیر قابل استفاده است.');
        }
    }

    public function projectFor(User $user): Project
    {
        $this->assertInvestee($user);

        return Project::query()
            ->where('user_id', $user->getKey())
            ->with('company')
            ->firstOrFail();
    }

    public function ensureCompany(Project $project, User $user): Company
    {
        $this->assertInvestee($user);

        if ((int) $project->user_id !== (int) $user->getKey()) {
            throw new AuthorizationException('پرونده انتخاب‌شده متعلق به کاربر جاری نیست.');
        }

        if ($project->company) {
            return $project->company;
        }

        $existing = Company::query()->where('user_id', $user->getKey())->first();
        if ($existing) {
            $project->forceFill(['company_id' => $existing->getKey()])->save();
            $project->setRelation('company', $existing);

            return $existing;
        }

        $company = Company::query()->create([
            'user_id' => $user->getKey(),
            'title' => $project->title,
            'company_name' => $project->company_name ?: $project->title,
            'registration_number' => $project->registration_number,
            'registration_date' => $this->nullableDate($project->registration_date),
            'national_id' => $project->national_id,
            'economic_code' => $project->economic_code,
            'legal_type' => $project->legal_type,
            'phone' => $project->tel,
            'email' => $project->email,
            'website' => $project->website,
            'province' => $project->state ? (string) $project->state : null,
            'city' => $project->city ? (string) $project->city : null,
            'address' => $project->address,
            'postal_code' => $project->postal_code,
            'ceo_name' => $project->CEO,
            'ceo_national_code' => $project->ceo_national_code,
        ]);

        $project->forceFill(['company_id' => $company->getKey()])->save();
        $project->setRelation('company', $company);

        return $company;
    }

    public function updateCompanyAndProject(User $user, array $data): Project
    {
        return DB::transaction(function () use ($user, $data): Project {
            $project = Project::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $company = $this->ensureCompany($project, $user);

            $company->fill([
                'user_id' => $user->getKey(),
                'title' => $data['title'],
                'company_name' => $data['company_name'],
                'registration_number' => $data['registration_number'] ?? null,
                'registration_date' => $data['registration_date'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'economic_code' => $data['economic_code'] ?? null,
                'legal_type' => $data['legal_type'] ?? null,
                'phone' => $data['tel'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'province' => isset($data['state']) ? (string) $data['state'] : null,
                'city' => isset($data['city']) ? (string) $data['city'] : null,
                'address' => $data['address'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'ceo_name' => $project->CEO ?: $user->name,
            ])->save();

            $project->forceFill([
                'company_id' => $company->getKey(),
                'title' => $data['title'],
                'company_name' => $data['company_name'],
                'registration_number' => $data['registration_number'] ?? null,
                'registration_date' => $data['registration_date'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'economic_code' => $data['economic_code'] ?? null,
                'legal_type' => $data['legal_type'] ?? null,
                'tel' => $data['tel'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'state' => $data['state'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
            ])->save();

            $project->members()->update(['company_id' => $company->getKey()]);

            $this->activityLog->record(
                'investee.profile_updated',
                sprintf('اطلاعات شرکت/طرح پرونده #%d توسط نماینده سرمایه‌پذیر به‌روزرسانی شد.', $project->getKey()),
                (int) $user->getKey()
            );

            return $project->fresh('company');
        }, 3);
    }

    private function nullableDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
