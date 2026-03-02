<?php

namespace Hashcrypttech\HashGuardian;

use Illuminate\Support\Str;

class IncomingEntry
{
    public string $uuid;

    public ?string $batchId = null;

    public ?string $familyHash = null;

    public array $tags = [];

    public function __construct(
        public string $type,
        public array $content,
        public ?float $duration = null,
        public ?string $status = null,
    ) {
        $this->uuid = Str::uuid()->toString();
    }

    public static function make(string $type, array $content): static
    {
        return new static($type, $content);
    }

    public function batchId(string $batchId): static
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function familyHash(string $hash): static
    {
        $this->familyHash = $hash;

        return $this;
    }

    public function tags(array $tags): static
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }

    public function tag(string $tag): static
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function duration(float $milliseconds): static
    {
        $this->duration = $milliseconds;

        return $this;
    }

    public function status(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'family_hash' => $this->familyHash,
            'content' => json_encode($this->content),
            'duration' => $this->duration,
            'status' => $this->status,
            'created_at' => now(),
        ];
    }
}
