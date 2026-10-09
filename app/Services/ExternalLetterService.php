<?php

namespace App\Services;

use App\Models\ExternalLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ExternalLetterService
{
    public function __construct(private readonly EnterpriseRecordAccess $access, private readonly BusinessAudit $audit) {}

    public function save(array $data, User $actor, ?int $id = null): ExternalLetter
    {
        if (! empty($data['project_id'])) {
            $this->access->assertProject($actor, (int) $data['project_id']);
        }
        $this->access->assertAttachment($data['media_file_id'] ?? null, $data['project_id'] ?? null, $actor);

        return DB::transaction(function () use ($data, $actor, $id): ExternalLetter {
            $letter = $id ? $this->access->letters($actor)->lockForUpdate()->findOrFail($id) : new ExternalLetter;
            $before = $id ? $letter->getAttributes() : null;
            if ($id) {
                abort_unless($letter->status !== 'closed' && $letter->lock_version === (int) $data['lock_version'], 409);
                abort_unless($letter->direction === $data['direction'], 422, 'جهت نامه ثبت‌شده قابل تغییر نیست.');
            }
            unset($data['lock_version']);
            $letter->fill($data);
            $letter->created_by ??= $actor->id;
            $letter->lock_version = ($letter->lock_version ?? 0) + 1;
            $letter->save();
            $this->audit->record($letter, $id ? 'updated' : 'registered', $before);

            return $letter;
        }, 3);
    }

    public function transition(int $id, string $status, int $version, ?string $note, User $actor): void
    {
        DB::transaction(function () use ($id, $status, $version, $note, $actor): void {
            $letter = $this->access->letters($actor)->lockForUpdate()->findOrFail($id);
            abort_unless($letter->lock_version === $version, 409);
            $allowed = ['registered' => ['in_progress', 'closed'], 'in_progress' => ['closed']];
            abort_unless(in_array($status, $allowed[$letter->status] ?? [], true), 409);
            $before = $letter->getAttributes();
            $letter->update(['status' => $status, 'completion_note' => $note, 'lock_version' => $version + 1]);
            $this->audit->record($letter, 'status_changed', $before);
        }, 3);
    }
}
