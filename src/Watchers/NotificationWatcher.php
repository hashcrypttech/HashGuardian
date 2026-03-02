<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class NotificationWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(NotificationSent::class, [$this, 'handleNotificationSent']);
    }

    public function handleNotificationSent(NotificationSent $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $entry = IncomingEntry::make(EntryType::NOTIFICATION, [
            'notification' => get_class($event->notification),
            'channel' => $event->channel,
            'notifiable' => $this->formatNotifiable($event->notifiable),
            'response' => $this->formatResponse($event->response),
            'queued' => in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements($event->notification) ?: []
            ),
        ]);

        $entry->familyHash(md5(get_class($event->notification) . $event->channel));
        $entry->status('sent');
        $entry->tags([
            'notification:' . get_class($event->notification),
            'channel:' . $event->channel,
        ]);

        if ($notifiableId = $this->getNotifiableId($event->notifiable)) {
            $entry->tag('notifiable:' . $notifiableId);
        }

        $this->record($entry);
    }

    protected function formatNotifiable($notifiable): array
    {
        return [
            'type' => get_class($notifiable),
            'id' => $this->getNotifiableId($notifiable),
        ];
    }

    protected function getNotifiableId($notifiable): ?string
    {
        if (method_exists($notifiable, 'getKey')) {
            return (string) $notifiable->getKey();
        }

        if (property_exists($notifiable, 'id')) {
            return (string) $notifiable->id;
        }

        return null;
    }

    protected function formatResponse($response): ?string
    {
        if (is_null($response)) {
            return null;
        }

        if (is_string($response)) {
            return $response;
        }

        if (is_object($response)) {
            return get_class($response);
        }

        return json_encode($response);
    }
}
