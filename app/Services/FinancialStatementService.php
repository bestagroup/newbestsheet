<?php

namespace App\Services;

use App\Models\Financial_statement;
use App\Models\Project;
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
            $this->lockProject((int) $data['project_id']);
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

    private function lockProject(int $id): void
    {
        // Historical portfolio records remain maintainable after exit.
        Project::query()->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
            ->lockForUpdate()->findOrFail($id);
    }

    private function lockStatement(int $id): Financial_statement
    {
        $projectId = Financial_statement::query()->findOrFail($id)->project_id;
        $this->lockProject((int) $projectId);

        return Financial_statement::query()->where('project_id', $projectId)->lockForUpdate()->findOrFail($id);
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
