<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PortfolioMeeting extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['agenda' => 'array', 'lock_version' => 'integer'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function resolutions()
    {
        return $this->hasMany(MeetingResolution::class);
    }

    public function media()
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public static function statuses(): array
    {
        return ['draft' => 'پیش‌نویس', 'held' => 'برگزارشده', 'finalized' => 'نهایی', 'cancelled' => 'لغوشده'];
    }
}
