<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('hashguardian.storage.database.connection');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('hashguardian_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->char('batch_id', 36)->index();
            $table->string('type', 20)->index();
            $table->string('family_hash', 64)->nullable()->index();
            $table->longText('content');
            $table->float('duration')->nullable();
            $table->string('status', 20)->nullable();
            $table->dateTime('created_at')->nullable()->index();

            $table->index(['type', 'created_at']);
            $table->index(['batch_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('hashguardian_entries');
    }
};
