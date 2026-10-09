<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ExternalLetter extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['confidential' => 'boolean', 'due_on' => 'date', 'lock_version' => 'integer'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function media()
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public function getRegisterNumberAttribute(): string
    {
        return ($this->direction === 'incoming' ? 'IN-' : 'OUT-').str_pad((string) $this->id, 8, '0', STR_PAD_LEFT);
    }

    public static function statuses(): array
    {
        return ['registered' => 'ثبت‌شده', 'in_progress' => 'در حال پیگیری', 'closed' => 'مختومه'];
    }
}
