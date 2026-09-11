<?php

namespace Tests\Unit;

use App\Models\Calendar as CalendarModel;
use App\Models\User;
use App\Services\GoogleCalendarSyncService;
use App\Services\GoogleService;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\Resource\Events;
use Mockery;
use Tests\TestCase;

class GoogleCalendarSyncServiceTest extends TestCase
{
    public function test_it_invites_assigned_users_and_requests_google_notifications(): void
    {
        config()->set([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);

        $organizer = new User;
        $organizer->forceFill([
            'email' => 'organizer@gmail.com',
            'google_token' => 'access-token',
            'google_refresh_token' => 'refresh-token',
        ]);

        $calendar = Mockery::mock(CalendarModel::class)->makePartial();
        $calendar->forceFill([
            'title' => 'Portfolio review',
            'description' => 'Quarterly review',
            'location' => 'Online',
            'start' => '1405-06-05 10:00:00',
            'end' => '1405-06-05 11:00:00',
        ]);
        $calendar->shouldReceive('save')->once()->andReturnTrue();

        $events = Mockery::mock(Events::class);
        $events->shouldReceive('insert')
            ->once()
            ->withArgs(function (string $calendarId, Event $event, array $options): bool {
                $emails = collect($event->getAttendees())
                    ->map(fn ($attendee) => $attendee instanceof EventAttendee
                        ? $attendee->getEmail()
                        : $attendee['email'])
                    ->all();

                return $calendarId === 'primary'
                    && $options === ['sendUpdates' => 'all']
                    && $emails === ['guest@gmail.com', 'workspace@example.com'];
            })
            ->andReturn(new Event(['id' => 'google-event-id']));

        $googleCalendar = Mockery::mock(GoogleCalendar::class);
        $googleCalendar->events = $events;

        $google = Mockery::mock(GoogleService::class);
        $google->shouldReceive('getClient')
            ->once()
            ->with($organizer)
            ->andReturn($googleCalendar);

        $service = new GoogleCalendarSyncService($google);
        $service->sync($calendar, $organizer, [
            'organizer@gmail.com',
            'Guest@Gmail.com',
            'guest@gmail.com',
            'workspace@example.com',
            'not-an-email',
        ]);

        $this->assertSame('google-event-id', $calendar->google_event_id);
        $this->assertSame('synced', $calendar->google_sync_status);
        $this->assertNull($calendar->google_sync_error);
    }
}
