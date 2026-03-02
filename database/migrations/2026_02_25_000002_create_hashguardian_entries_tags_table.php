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
        Schema::connection($this->getConnection())->create('hashguardian_entries_tags', function (Blueprint $table) {
            $table->char('entry_uuid', 36);
            $table->string('tag', 255)->index();

            $table->foreign('entry_uuid')
                ->references('uuid')
                ->on('hashguardian_entries')
                ->cascadeOnDelete();

            $table->index(['entry_uuid', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('hashguardian_entries_tags');
    }
};
