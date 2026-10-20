<?php

namespace App\Services;

use App\Models\Financial_statement;
use App\Models\FinancialStatementRevision;
use App\Models\Project;
use App\Models\User;
use App\Support\FinancialPeriod;
use App\Support\Monetary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class FinancialStatementReviewService
{
    public function __construct(private readonly BusinessAudit $audit) {}

    public function transition(int $id, string $action, User $actor, int $expectedVersion, ?string $note = null): Financial_statement
    {
        abort_unless(in_array($action, ['submit', 'approve', 'return', 'revise'], true), 404);
        Gate::forUser($actor)->authorize('can-access', ['financialstatement', 'view']);
        Gate::forUser($actor)->authorize('can-access', [in_array($action, ['approve', 'return'], true) ? 'financialstatement-review' : 'financialstatement', 'edit']);

        return DB::transaction(function () use ($id, $action, $actor, $expectedVersion, $note): Financial_statement {
            $projectId = Financial_statement::findOrFail($id)->project_id;
            Project::where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)->lockForUpdate()->findOrFail($projectId);
            $row = Financial_statement::where('project_id', $projectId)->lockForUpdate()->findOrFail($id);
            $this->ensure($row->lock_version === $expectedVersion, 'سند تغییر کرده است؛ صفحه را تازه کنید.');
            $before = $row->getAttributes();
            if ($action === 'submit') {
                $this->ensure(in_array($row->workflow_status, ['draft', 'legacy'], true), 'فقط پیش‌نویس قابل ارسال برای بررسی است.');
                $this->validateFinancials($row);
                $row->workflow_status = 'submitted';
                $row->submitted_by = $actor->id;
                $row->submitted_at = now();
                $row->preparer_ids = array_values(array_unique([...($row->preparer_ids ?? []), $actor->id]));
            } elseif ($action === 'revise') {
                $this->ensure($row->workflow_status === 'approved', 'فقط سند تأییدشده قابل ایجاد اصلاحیه است.');
                $this->ensure(trim((string) $note) !== '', 'دلیل اصلاحیه الزامی است.');
                $row->revision++;
                $row->workflow_status = 'draft';
                $row->preparer_ids = [$actor->id];
                $row->submitted_by = null;
                $row->submitted_at = null;
            } else {
                $this->ensure($row->workflow_status === 'submitted', 'سند در انتظار بررسی نیست.');
                // Business invariant, deliberately outside Gate::before (including superadmin).
                $this->ensure(! in_array((int) $actor->id, array_map('intval', $row->preparer_ids ?? []), true)
                    && (int) $row->submitted_by !== (int) $actor->id, 'تهیه‌کننده یا ارسال‌کننده نمی‌تواند سند خود را بررسی و تأیید کند.');
                if ($action === 'return') {
                    $this->ensure(trim((string) $note) !== '', 'دلیل بازگشت الزامی است.');
                    $row->workflow_status = 'draft';
                } else {
                    $this->validateFinancials($row);
                    FinancialStatementRevision::create([
                        'financial_statement_id' => $row->id, 'revision' => $row->revision,
                        'payload' => $row->only(Financial_statement::snapshotFields()),
                        'approved_by' => $actor->id, 'approved_at' => now(), 'review_note' => $note,
                    ]);
                    $row->workflow_status = 'approved';
                }
            }
            $row->review_note = $note;
            $row->lock_version++;
            $row->save();
            $this->audit->record($row, 'review_'.$action, $before);

            return $row;
        }, 3);
    }

    private function validateFinancials(Financial_statement $row): void
    {
        $this->ensure($row->company_id && Financial_statement::consistentOwner()->whereKey($row->id)->exists(), 'مالکیت شرکت در سند باید مشخص و با پرونده منطبق باشد.');
        $this->ensure(in_array($row->income_basis, ['standalone', 'ytd'], true) && in_array($row->period_type, ['annual', 'quarterly'], true), 'نوع و مبنای دوره باید استاندارد باشد.');
        if ($errors = FinancialPeriod::errors($row->getAttributes())) {
            throw ValidationException::withMessages($errors);
        }
        $duplicate = Financial_statement::where('id', '<>', $row->id)->where('year', $row->year)->where('month', $row->month)->where('period_type', $row->period_type)
            ->where(fn ($q) => $q->where('company_id', $row->company_id)->orWhere(fn ($q) => $q->whereNull('company_id')->whereHas('project', fn ($p) => $p->where('company_id', $row->company_id))))->exists();
        $this->ensure(! $duplicate, 'برای این شرکت و دوره سند تکراری وجود دارد؛ ابتدا تعارض را رفع کنید.');
        foreach (['net_profit', 'total_assets', 'total_liabilities', 'total_equity'] as $field) {
            $this->ensure($row->{$field} !== null && trim((string) $row->{$field}) !== '', 'سود خالص و جمع دارایی، بدهی و حقوق مالکانه باید مشخص باشند.');
        }
        foreach (Financial_statement::monetaryFields() as $field) {
            if ($row->{$field} !== null) {
                $this->ensure(is_numeric($row->{$field}), 'مبلغ نامعتبر در '.$field);
            }
        }
        $this->ensure(! Monetary::value($row->total_assets)->isNegative() && ! Monetary::value($row->total_liabilities)->isNegative(), 'جمع دارایی و بدهی نمی‌تواند منفی باشد.');
        $this->ensure(Monetary::value($row->total_assets)->isEqualTo(Monetary::value($row->total_liabilities)->plus(Monetary::value($row->total_equity))), 'ترازنامه تراز نیست: دارایی باید برابر بدهی به‌علاوه حقوق مالکانه باشد.');
        foreach (['total_assets' => ['total_current_assets', 'total_non_current_assets'], 'total_liabilities' => ['total_current_liabilities', 'total_non_current_liabilities']] as $total => $parts) {
            if ($row->{$parts[0]} !== null && $row->{$parts[1]} !== null) {
                $this->ensure(Monetary::value($row->{$total})->isEqualTo(Monetary::value($row->{$parts[0]})->plus(Monetary::value($row->{$parts[1]}))), 'جمع اجزای ترازنامه با مبلغ کل سازگار نیست: '.$total);
            }
        }
        if ($row->total_equity_and_liabilities !== null) {
            $this->ensure(Monetary::value($row->total_assets)->isEqualTo(Monetary::value($row->total_equity_and_liabilities)), 'جمع حقوق مالکانه و بدهی با دارایی برابر نیست.');
        }
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }
}
