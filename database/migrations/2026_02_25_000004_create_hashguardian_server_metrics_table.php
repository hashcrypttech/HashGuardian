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
        Schema::connection($this->getConnection())->create('hashguardian_server_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');

            // CPU
            $table->float('cpu_percent')->default(0);
            $table->float('cpu_load_1m')->default(0);
            $table->float('cpu_load_5m')->default(0);
            $table->float('cpu_load_15m')->default(0);
            $table->unsignedTinyInteger('cpu_cores')->default(1);

            // RAM
            $table->unsignedBigInteger('ram_total')->default(0);
            $table->unsignedBigInteger('ram_used')->default(0);
            $table->unsignedBigInteger('ram_free')->default(0);
            $table->unsignedBigInteger('ram_available')->default(0);
            $table->float('ram_percent')->default(0);

            // SWAP
            $table->unsignedBigInteger('swap_total')->default(0);
            $table->unsignedBigInteger('swap_used')->default(0);
            $table->unsignedBigInteger('swap_free')->default(0);
            $table->float('swap_percent')->default(0);

            // Disk
            $table->unsignedBigInteger('disk_total')->default(0);
            $table->unsignedBigInteger('disk_used')->default(0);
            $table->unsignedBigInteger('disk_free')->default(0);
            $table->float('disk_percent')->default(0);
            $table->string('disk_mount', 50)->default('/');

            // Network
            $table->unsignedBigInteger('net_bytes_in')->default(0);
            $table->unsignedBigInteger('net_bytes_out')->default(0);

            // Processes
            $table->unsignedInteger('process_count')->default(0);
            $table->unsignedInteger('php_fpm_active')->nullable();
            $table->unsignedInteger('php_fpm_idle')->nullable();

            // PHP
            $table->unsignedBigInteger('php_memory_limit')->nullable();
            $table->float('opcache_used')->nullable();

            $table->dateTime('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('hashguardian_server_metrics');
    }
};
