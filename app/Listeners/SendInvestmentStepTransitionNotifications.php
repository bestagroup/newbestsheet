<?php

namespace App\Listeners;

use App\Events\InvestmentStepTransitioned;
use App\Services\OperationalNotificationService;
use App\Services\OperationalRecipientResolver;

class SendInvestmentStepTransitionNotifications
{
    public function __construct(
        private readonly OperationalRecipientResolver $recipients,
        private readonly OperationalNotificationService $notifications,
    ) {}

    public function handle(InvestmentStepTransitioned $event): void
    {
        $projectStep = $event->projectStep->loadMissing('project.user', 'project.company');
        $project = $projectStep->project;
        if (! $project) {
            return;
        }

        $approved = $projectStep->status === 'approved';
        $statusLabel = $approved ? 'تأیید' : 'رد';
        $eventKey = $approved ? 'workflow.step_approved' : 'workflow.step_rejected';
        $title = $approved ? 'مرحله سرمایه‌گذاری تأیید شد' : 'مرحله سرمایه‌گذاری رد شد';
        $message = sprintf('مرحله «%s» پرونده «%s» %s شد.', $projectStep->title, $project->title, $statusLabel);

        if (! $approved && filled($projectStep->description)) {
            $message .= ' توضیح: '.trim((string) $projectStep->description);
        }

        $sms = $message;
        $investeeUsers = $this->recipients->investeeUsers($project);
        $this->notifications->deliver(
            $eventKey,
            'project-step:'.$projectStep->getKey(),
            $projectStep,
            $investeeUsers,
            [
                'title' => $title,
                'message' => $message,
                'url' => route('profile').'#navs-investment-card',
                'icon' => $approved ? 'mdi-check-decagram-outline' : 'mdi-close-octagon-outline',
                'category' => 'workflow',
                'severity' => $approved ? 'success' : 'warning',
                'actor_id' => $projectStep->user_id,
            ],
            $sms,
            $this->recipients->investeePhones($project)
        );
    }
}
