<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('hashguardian.storage.database.connection', config('database.default'));

        Schema::connection($connection)->create('hashguardian_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('metric', 50);
            $table->string('dimension', 255)->nullable();
            $table->string('period', 10);
            $table->timestamp('bucket');
            $table->decimal('value', 16, 4);
            $table->unsignedBigInteger('sample_count')->default(0);
            $table->timestamps();

            $table->unique(['type', 'metric', 'dimension', 'period', 'bucket'], 'hashguardian_agg_unique');
            $table->index(['type', 'period', 'bucket'], 'hashguardian_agg_lookup');
            $table->index(['period', 'bucket'], 'hashguardian_agg_rollup');
        });
    }

    public function down(): void
    {
        $connection = config('hashguardian.storage.database.connection', config('database.default'));
        Schema::connection($connection)->dropIfExists('hashguardian_aggregates');
    }
};
