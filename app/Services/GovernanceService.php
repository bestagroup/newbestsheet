<?php

namespace App\Services;

use App\Models\PortfolioMeeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GovernanceService
{
    public function __construct(private readonly BusinessAudit $audit, private readonly EnterpriseRecordAccess $access) {}

    public function save(array $data, User $actor, ?int $id = null): PortfolioMeeting
    {
        $this->access->assertProject($actor, (int) $data['project_id']);
        $this->access->assertAttachment($data['media_file_id'] ?? null, (int) $data['project_id'], $actor);

        return DB::transaction(function () use ($data, $actor, $id): PortfolioMeeting {
            $meeting = $id ? PortfolioMeeting::query()->lockForUpdate()->findOrFail($id) : new PortfolioMeeting;
            $before = $id ? $meeting->getAttributes() : null;
            if ($id) {
                $this->access->assertProject($actor, (int) $meeting->project_id);
                abort_unless($meeting->status === 'draft' && ! $meeting->resolutions()->exists(), 409, 'جلسه دارای مصوبه یا غیرپیش‌نویس قابل بازنویسی نیست.');
                abort_unless($meeting->lock_version === (int) $data['lock_version'], 409, 'اطلاعات تغییر کرده؛ صفحه را تازه کنید.');
                abort_unless((int) $meeting->project_id === (int) $data['project_id'], 422);
            }
            unset($data['lock_version']);
            $meeting->fill($data);
            $meeting->created_by ??= $actor->id;
            $meeting->lock_version = ($meeting->lock_version ?? 0) + 1;
            $meeting->save();
            $this->audit->record($meeting, $id ? 'updated' : 'created', $before);

            return $meeting;
        }, 3);
    }

    public function transition(int $id, string $status, int $version, User $actor): void
    {
        DB::transaction(function () use ($id, $status, $version, $actor): void {
            $meeting = PortfolioMeeting::query()->lockForUpdate()->findOrFail($id);
            $this->access->assertProject($actor, (int) $meeting->project_id);
            abort_unless($meeting->lock_version === $version, 409);
            $allowed = ['draft' => ['held', 'cancelled'], 'held' => ['finalized', 'cancelled']];
            abort_unless(in_array($status, $allowed[$meeting->status] ?? [], true), 409);
            if ($status === 'finalized') {
                $covered = $meeting->resolutions()->pluck('agenda_index')->unique();
                if (count($meeting->agenda) !== $covered->count()) {
                    throw ValidationException::withMessages(['status' => 'برای هر بند دستور جلسه یک مصوبه یا نتیجه بررسی ثبت کنید.']);
                }
            }
            $before = $meeting->getAttributes();
            $meeting->update(['status' => $status, 'lock_version' => $version + 1]);
            $this->audit->record($meeting, 'status_changed', $before);
        }, 3);
    }

    public function addResolution(int $id, array $data, User $actor): void
    {
        DB::transaction(function () use ($id, $data, $actor): void {
            $meeting = PortfolioMeeting::query()->lockForUpdate()->findOrFail($id);
            $this->access->assertProject($actor, (int) $meeting->project_id);
            abort_unless(in_array($meeting->status, ['draft', 'held'], true), 409);
            if (! array_key_exists((int) $data['agenda_index'], $meeting->agenda)) {
                throw ValidationException::withMessages(['agenda_index' => 'بند دستور جلسه معتبر نیست.']);
            }
            $resolution = $meeting->resolutions()->create($data);
            $meeting->increment('lock_version');
            $this->audit->record($resolution, 'created');
        }, 3);
    }

    public function completeResolution(int $meetingId, int $id, string $note, User $actor): void
    {
        DB::transaction(function () use ($meetingId, $id, $note, $actor): void {
            $meeting = PortfolioMeeting::query()->lockForUpdate()->findOrFail($meetingId);
            $this->access->assertProject($actor, (int) $meeting->project_id);
            abort_unless($meeting->status === 'finalized', 409, 'ابتدا صورتجلسه نهایی شود.');
            $resolution = $meeting->resolutions()->lockForUpdate()->findOrFail($id);
            abort_unless($resolution->status === 'pending', 409);
            $before = $resolution->getAttributes();
            $resolution->update(['status' => 'completed', 'completion_note' => $note, 'completed_at' => now()]);
            $this->audit->record($resolution, 'completed', $before);
        }, 3);
    }
}
