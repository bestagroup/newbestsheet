<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class BusinessAudit
{
    /** Must be called inside the same transaction as the business mutation. */
    public function record(Model $subject, string $action, ?array $before = null): void
    {
        DB::table('business_audits')->insert([
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'actor_id' => auth()->id(),
            'action' => $action,
            'before' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after' => json_encode($subject->getAttributes(), JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
