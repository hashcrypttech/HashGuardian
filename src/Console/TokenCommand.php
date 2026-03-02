<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TokenCommand extends Command
{
    protected $signature = 'hashguardian:token
        {action : Action to perform (create, list, revoke)}
        {name? : Name for the token (required for create)}
        {--abilities= : Comma-separated abilities (read,export,metrics)}
        {--expires= : Expiration in days}
        {--id= : Token ID (required for revoke)}';

    protected $description = 'Manage HashGuardian API tokens';

    public function handle(): void
    {
        $action = $this->argument('action');

        match ($action) {
            'create' => $this->createToken(),
            'list' => $this->listTokens(),
            'revoke' => $this->revokeToken(),
            default => $this->error("Unknown action: {$action}. Use create, list, or revoke."),
        };
    }

    protected function createToken(): void
    {
        $name = $this->argument('name');
        if (! $name) {
            $this->error('Token name is required: hashguardian:token create "My Token"');
            return;
        }

        $plainToken = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        $abilities = $this->option('abilities')
            ? explode(',', $this->option('abilities'))
            : ['read'];

        $expiresAt = $this->option('expires')
            ? now()->addDays((int) $this->option('expires'))
            : null;

        $connection = config('hashguardian.storage.database.connection', config('database.default'));

        DB::connection($connection)->table('hashguardian_api_tokens')->insert([
            'name' => $name,
            'token' => $hashedToken,
            'abilities' => json_encode(array_map('trim', $abilities)),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Token created successfully!');
        $this->newLine();
        $this->warn('Save this token — it will not be shown again:');
        $this->newLine();
        $this->line($plainToken);
        $this->newLine();
        $this->info("Name: {$name}");
        $this->info('Abilities: ' . implode(', ', $abilities));
        if ($expiresAt) {
            $this->info("Expires: {$expiresAt->toDateTimeString()}");
        }
    }

    protected function listTokens(): void
    {
        $connection = config('hashguardian.storage.database.connection', config('database.default'));

        $tokens = DB::connection($connection)
            ->table('hashguardian_api_tokens')
            ->orderByDesc('created_at')
            ->get();

        if ($tokens->isEmpty()) {
            $this->info('No API tokens found.');
            return;
        }

        $rows = $tokens->map(fn ($t) => [
            $t->id,
            $t->name,
            implode(', ', json_decode($t->abilities ?? '[]', true)),
            $t->last_used_at ?? 'Never',
            $t->expires_at ?? 'Never',
            $t->created_at,
        ])->toArray();

        $this->table(['ID', 'Name', 'Abilities', 'Last Used', 'Expires', 'Created'], $rows);
    }

    protected function revokeToken(): void
    {
        $id = $this->option('id') ?? $this->argument('name');

        if (! $id || ! is_numeric($id)) {
            $this->error('Token ID is required: hashguardian:token revoke --id=1');
            return;
        }

        $connection = config('hashguardian.storage.database.connection', config('database.default'));

        $deleted = DB::connection($connection)
            ->table('hashguardian_api_tokens')
            ->where('id', (int) $id)
            ->delete();

        if ($deleted) {
            $this->info("Token #{$id} revoked successfully.");
        } else {
            $this->error("Token #{$id} not found.");
        }
    }
}
