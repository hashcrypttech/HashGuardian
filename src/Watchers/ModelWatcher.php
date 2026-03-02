<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;
use Hashcrypttech\HashGuardian\HashGuardian;

class ModelWatcher extends Watcher
{
    protected array $hydrationEntries = [];

    public function register(Application $app): void
    {
        $app['events']->listen(
            $this->option('events', 'eloquent.*'),
            [$this, 'handleEloquentEvent']
        );

        app(HashGuardian::class)->afterStoring(function () {
            $this->flush();
        });
    }

    public function handleEloquentEvent(string $event, array $data): void
    {
        if (! $this->isEnabled() || ! $this->shouldRecord($event)) {
            return;
        }

        $model = $data['model'] ?? $data[0] ?? null;

        if (! $model instanceof Model) {
            return;
        }

        if (Str::is('*retrieved*', $event)) {
            $this->recordHydration($model);
            return;
        }

        $modelClass = get_class($model);
        $modelIdentifier = $modelClass . ':' . $model->getKey();
        $action = $this->extractAction($event);
        $changes = $model->getChanges();

        $entry = IncomingEntry::make(EntryType::MODEL, array_filter([
            'action' => $action,
            'model' => $modelIdentifier,
            'model_class' => $modelClass,
            'key' => $model->getKey(),
            'changes' => empty($changes) ? null : $changes,
        ]));

        $entry->familyHash(md5($modelClass . ':' . $action));
        $entry->status($action);
        $entry->tags([
            'model:' . class_basename($modelClass),
            'action:' . $action,
        ]);

        $this->record($entry);
    }

    protected function recordHydration(Model $model): void
    {
        if (! $this->option('hydrations', false)) {
            return;
        }

        $modelClass = get_class($model);

        if ($this->shouldIgnoreHydration($modelClass)) {
            return;
        }

        if (! isset($this->hydrationEntries[$modelClass])) {
            $entry = IncomingEntry::make(EntryType::MODEL, [
                'action' => 'retrieved',
                'model' => $modelClass,
                'model_class' => $modelClass,
                'count' => 1,
            ]);

            $entry->familyHash(md5($modelClass . ':retrieved'));
            $entry->status('retrieved');
            $entry->tags([
                'model:' . class_basename($modelClass),
                'action:retrieved',
            ]);

            $this->hydrationEntries[$modelClass] = $entry;
            $this->record($entry);
        } else {
            $existingEntry = $this->hydrationEntries[$modelClass];

            if (is_string($existingEntry->content)) {
                $existingEntry->content = json_decode($existingEntry->content, true);
            }

            $existingEntry->content['count']++;
        }
    }

    protected function flush(): void
    {
        $this->hydrationEntries = [];
    }

    protected function extractAction(string $event): string
    {
        preg_match('/\.(.*):/', $event, $matches);

        return $matches[1] ?? 'unknown';
    }

    protected function shouldRecord(string $eventName): bool
    {
        return Str::is([
            '*created*', '*updated*', '*restored*', '*deleted*', '*retrieved*',
        ], $eventName);
    }

    protected function shouldIgnoreHydration(string $modelClass): bool
    {
        $ignoreModels = $this->option('ignore', []);

        return collect($ignoreModels)->contains(function ($class) use ($modelClass) {
            return $modelClass === $class || is_subclass_of($modelClass, $class);
        });
    }
}
