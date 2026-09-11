<?php

namespace App\Events;

use App\Models\Project_step;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentStepTransitioned
{
    use Dispatchable, SerializesModels;

    public function __construct(public Project_step $projectStep) {}
}
