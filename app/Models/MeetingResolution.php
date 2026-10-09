<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MeetingResolution extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['due_on' => 'date', 'completed_at' => 'datetime'];

    public function meeting()
    {
        return $this->belongsTo(PortfolioMeeting::class, 'portfolio_meeting_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
