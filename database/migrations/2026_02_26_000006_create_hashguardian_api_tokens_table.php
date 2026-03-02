<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('hashguardian.storage.database.connection', config('database.default'));

        Schema::connection($connection)->create('hashguardian_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 80)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('hashguardian.storage.database.connection', config('database.default'));
        Schema::connection($connection)->dropIfExists('hashguardian_api_tokens');
    }
};
