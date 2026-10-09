<?php

namespace App\Services;

use App\Models\Finance;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinanceService
{
    public function __construct(private readonly BusinessAudit $audit) {}

    public function create(array $data): Finance
    {
        return DB::transaction(function () use ($data): Finance {
            Project::query()->lockForUpdate()->findOrFail($data['project_id']);
            if (! empty($data['idempotency_key'])) {
                $existing = Finance::query()->where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    foreach (['project_id', 'amount', 'serial', 'docserial', 'date', 'description'] as $field) {
                        if ((string) $existing->{$field} !== (string) ($data[$field] ?? '')) {
                            throw ValidationException::withMessages(['idempotency_key' => 'کلید درخواست با اطلاعات پرداخت دیگری استفاده شده است.']);
                        }
                    }

                    return $existing;
                }
            }
            if (! empty($data['idempotency_key']) && DB::table('business_audits')->where('subject_type', Finance::class)->where('after->idempotency_key', $data['idempotency_key'])->exists()) {
                throw ValidationException::withMessages(['idempotency_key' => 'این درخواست قبلاً ثبت و سپس حذف شده است؛ درخواست تازه ثبت کنید.']);
            }
            $this->assertDocumentUnique($data);
            $finance = Finance::query()->create([...$data, 'finance_type' => 'vc-investment']);
            $this->audit->record($finance, 'created');

            return $finance;
        }, 3);
    }

    public function update(int $id, array $data): Finance
    {
        return DB::transaction(function () use ($id, $data): Finance {
            Project::query()->lockForUpdate()->findOrFail($data['project_id']);
            $finance = Finance::query()->lockForUpdate()->findOrFail($id);
            if ((int) $finance->project_id !== (int) $data['project_id']) {
                throw ValidationException::withMessages(['project_id' => 'انتقال پرداخت ثبت‌شده به پرونده دیگر مجاز نیست.']);
            }
            $this->assertDocumentUnique($data, $id);
            $before = $finance->getAttributes();
            unset($data['idempotency_key']);
            $finance->update([...$data, 'finance_type' => 'vc-investment']);
            $this->audit->record($finance, 'updated', $before);

            return $finance;
        }, 3);
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $finance = Finance::query()->lockForUpdate()->findOrFail($id);
            $this->audit->record($finance, 'deleted', $finance->getAttributes());
            $finance->delete();
        }, 3);
    }

    private function assertDocumentUnique(array $data, ?int $except = null): void
    {
        if (! empty($data['docserial']) && Finance::query()
            ->where('project_id', $data['project_id'])->where('docserial', $data['docserial'])
            ->when($except, fn ($query) => $query->whereKeyNot($except))->exists()) {
            throw ValidationException::withMessages(['docserial' => 'شماره سند برای این طرح قبلاً ثبت شده است.']);
        }
    }
}
