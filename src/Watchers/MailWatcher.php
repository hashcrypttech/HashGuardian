<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class MailWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(MessageSent::class, [$this, 'handleMessageSent']);
    }

    public function handleMessageSent(MessageSent $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $message = $event->message;

        $entry = IncomingEntry::make(EntryType::MAIL, [
            'mailable' => $this->getMailableClass($event),
            'subject' => $message->getSubject(),
            'to' => $this->formatAddresses($message->getTo()),
            'cc' => $this->formatAddresses($message->getCc()),
            'bcc' => $this->formatAddresses($message->getBcc()),
            'from' => $this->formatAddresses($message->getFrom()),
            'reply_to' => $this->formatAddresses($message->getReplyTo()),
            'queued' => isset($event->data['__laravel_notification_queued']),
        ]);

        $entry->familyHash(md5($this->getMailableClass($event) . $message->getSubject()));
        $entry->status('sent');
        $entry->tags([
            'mailable:' . $this->getMailableClass($event),
        ]);

        $this->record($entry);
    }

    protected function getMailableClass($event): string
    {
        if (isset($event->data['__laravel_notification'])) {
            return $event->data['__laravel_notification'];
        }

        return $event->data['mailable'] ?? 'Unknown';
    }

    protected function formatAddresses(?array $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        $formatted = [];
        foreach ($addresses as $address) {
            if (is_object($address) && method_exists($address, 'getAddress')) {
                $formatted[] = [
                    'address' => $address->getAddress(),
                    'name' => $address->getName(),
                ];
            } elseif (is_array($address)) {
                $formatted[] = $address;
            }
        }

        return $formatted;
    }
}
