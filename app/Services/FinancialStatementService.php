<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Support\FinancialPeriod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Transaction boundary for validated financial-statement commands. */
final class FinancialStatementService
{
    public function __construct(private readonly BusinessAudit $audit) {}

    public function create(array $data): Financial_statement
    {
        return $this->persist(function () use ($data): Financial_statement {
            $project = $this->lockProject((int) $data['project_id']);
            $data = $this->prepare($project, $data);
            $statement = Financial_statement::query()->create($data);
            $this->audit->record($statement, 'created');

            return $statement;
        });
    }

    public function update(int $id, array $data): Financial_statement
    {
        return $this->persist(function () use ($id, $data): Financial_statement {
            $statement = $this->lockStatement($id);
            if ((int) $statement->project_id !== (int) $data['project_id']) {
                throw ValidationException::withMessages(['project_id' => 'انتقال صورت مالی ثبت‌شده به پرونده دیگر مجاز نیست.']);
            }
            $data = $this->prepare($this->lockProject((int) $statement->project_id), $data, $statement);
            $before = $statement->getAttributes();
            $statement->update($data);
            $this->audit->record($statement, 'updated', $before);

            return $statement;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $statement = $this->lockStatement($id);
            $this->audit->record($statement, 'deleted', $statement->getAttributes());
            $statement->delete();
        }, 3);
    }

    private function lockProject(int $id): Project
    {
        // Historical portfolio records remain maintainable after exit.
        return Project::query()->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
            ->lockForUpdate()->findOrFail($id);
    }

    private function lockStatement(int $id): Financial_statement
    {
        $projectId = Financial_statement::query()->findOrFail($id)->project_id;
        $this->lockProject((int) $projectId);

        return Financial_statement::query()->where('project_id', $projectId)->lockForUpdate()->findOrFail($id);
    }

    private function prepare(Project $project, array $data, ?Financial_statement $statement = null): array
    {
        if ($statement?->company_id && (int) $statement->company_id !== (int) $project->company_id) {
            throw ValidationException::withMessages(['project_id' => 'مالک شرکت در سند با پرونده متفاوت است؛ ابتدا ارتباط پرونده را بررسی کنید.']);
        }
        // Ignore supplied ownership; the locked project is the sole source of identity.
        $data['company_id'] = $statement?->company_id ?? $project->company_id;
        foreach (['period_start', 'period_end', 'income_basis'] as $field) {
            $data[$field] = array_key_exists($field, $data) ? $data[$field] : ($statement?->{$field} ?? ($field === 'income_basis' ? 'unconfirmed' : null));
        }
        if (! in_array($data['income_basis'], ['unconfirmed', 'standalone', 'ytd'], true)) {
            throw ValidationException::withMessages(['income_basis' => 'مبنای دوره معتبر نیست.']);
        }
        if ($errors = FinancialPeriod::errors($data)) {
            throw ValidationException::withMessages($errors);
        }
        foreach (['period_start', 'period_end'] as $field) {
            $data[$field] = FinancialPeriod::normalize($data[$field]);
        }
        if ($data['company_id']) {
            Company::query()->lockForUpdate()->findOrFail($data['company_id']);
            // Company row lock serializes writes through this service across different projects.
            $duplicate = Financial_statement::query()
                ->where(function ($query) use ($data): void {
                    $query->where('company_id', $data['company_id'])
                        ->orWhere(fn ($legacy) => $legacy->whereNull('company_id')->whereHas('project', fn ($p) => $p->where('company_id', $data['company_id'])));
                })
                ->where('year', $data['year'])->where('month', $data['month'])
                ->where('period_type', $data['period_type'] ?? 'legacy')
                ->when($statement, fn ($query) => $query->where('id', '<>', $statement->id))
                ->lockForUpdate()->first();
            if ($duplicate) {
                throw ValidationException::withMessages(['project_id' => 'برای این شرکت، نوع و پایان دوره در پرونده دیگری نیز سند وجود دارد؛ ابتدا تعارض سوابق را رفع کنید.']);
            }
        } elseif ($data['income_basis'] !== 'unconfirmed') {
            throw ValidationException::withMessages(['project_id' => 'برای ثبت دوره استاندارد، پرونده باید به شرکت ثبت‌شده متصل باشد.']);
        }
        if ($statement && $statement->income_basis !== 'unconfirmed' && $data['income_basis'] === 'unconfirmed') {
            throw ValidationException::withMessages(['income_basis' => 'حذف مشخصات دوره استاندارد مجاز نیست.']);
        }

        return $data;
    }

    private function persist(callable $operation): Financial_statement
    {
        try {
            return DB::transaction($operation, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // Validation is advisory; the database constraint also handles concurrent writes.
            throw ValidationException::withMessages(['project_id' => 'برای این پروژه و دوره مالی قبلاً صورت مالی ثبت شده است.']);
        }
    }
}
