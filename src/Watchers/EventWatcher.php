<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Closure;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use ReflectionFunction;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class EventWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen('*', [$this, 'handleEvent']);
    }

    public function handleEvent(string $eventName, array $payload): void
    {
        if (! $this->isEnabled() || $this->shouldIgnore($eventName)) {
            return;
        }

        $formattedPayload = $this->extractPayload($eventName, $payload);

        $entry = IncomingEntry::make(EntryType::EVENT, [
            'name' => $eventName,
            'payload' => empty($formattedPayload) ? null : $formattedPayload,
            'listeners' => $this->formatListeners($eventName),
            'broadcast' => class_exists($eventName)
                ? in_array(ShouldBroadcast::class, (array) class_implements($eventName))
                : false,
        ]);

        $entry->familyHash(md5($eventName));
        $entry->tags(['event:' . class_basename($eventName)]);

        $this->record($entry);
    }

    protected function extractPayload(string $eventName, array $payload): array
    {
        if (class_exists($eventName) && isset($payload[0]) && is_object($payload[0])) {
            return $this->extractObjectProperties($payload[0]);
        }

        return collect($payload)->map(function ($value) {
            if (is_object($value)) {
                return [
                    'class' => get_class($value),
                    'properties' => json_decode(json_encode($value), true),
                ];
            }
            return $value;
        })->toArray();
    }

    protected function extractObjectProperties(object $object): array
    {
        try {
            return json_decode(json_encode($object), true) ?: [];
        } catch (\Throwable $e) {
            return ['class' => get_class($object)];
        }
    }

    protected function formatListeners(string $eventName): array
    {
        try {
            return collect(app('events')->getListeners($eventName))
                ->map(function ($listener) {
                    $reflected = (new ReflectionFunction($listener))
                        ->getStaticVariables()['listener'] ?? null;

                    if ($reflected === null) {
                        return null;
                    }

                    if (is_string($reflected)) {
                        return [
                            'name' => Str::contains($reflected, '@') ? $reflected : $reflected . '@handle',
                            'queued' => false,
                        ];
                    }

                    if (is_array($reflected) && is_string($reflected[0])) {
                        $name = $reflected[0] . '@' . $reflected[1];
                        return [
                            'name' => $name,
                            'queued' => in_array(ShouldQueue::class, class_implements($reflected[0]) ?: []),
                        ];
                    }

                    if (is_array($reflected) && is_object($reflected[0])) {
                        $className = get_class($reflected[0]);
                        return [
                            'name' => $className . '@' . $reflected[1],
                            'queued' => in_array(ShouldQueue::class, class_implements($className) ?: []),
                        ];
                    }

                    if (is_object($reflected) && is_callable($reflected) && ! $reflected instanceof Closure) {
                        return [
                            'name' => get_class($reflected) . '@__invoke',
                            'queued' => false,
                        ];
                    }

                    return null;
                })
                ->filter()
                ->reject(fn ($listener) => Str::contains($listener['name'], 'Hashcrypttech\\HashGuardian'))
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function shouldIgnore(string $eventName): bool
    {
        if ($this->eventIsIgnored($eventName)) {
            return true;
        }

        if ($this->option('ignore_frameworks', true) && $this->eventIsFiredByFramework($eventName)) {
            return true;
        }

        return false;
    }

    protected function eventIsFiredByFramework(string $eventName): bool
    {
        return Str::is([
            'Illuminate\\*',
            'Laravel\\Octane\\*',
            'Laravel\\Scout\\Events\\ModelsImported',
            'eloquent*',
            'bootstrapped*',
            'bootstrapping*',
            'creating*',
            'composing*',
        ], $eventName);
    }

    protected function eventIsIgnored(string $eventName): bool
    {
        return Str::is($this->option('ignore', []), $eventName);
    }
}
