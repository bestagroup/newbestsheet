<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;

class HeaderNotificationComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            $view->with([
                'headerNotifications' => collect(),
                'headerUnreadNotificationCount' => 0,
            ]);

            return;
        }

        $view->with([
            'headerNotifications' => $user->notifications()->latest()->limit(6)->get(),
            'headerUnreadNotificationCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
