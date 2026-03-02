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
        Schema::connection($this->getConnection())->create('hashguardian_monitoring', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tag', 255)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('hashguardian_monitoring');
    }
};
