<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only approved snapshots. Changes require a new revision. */
class FinancialStatementRevision extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'revision' => 'integer', 'approved_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Approved snapshots are immutable.'));
        static::deleting(fn () => throw new \LogicException('Approved snapshots cannot be deleted.'));
    }
}
