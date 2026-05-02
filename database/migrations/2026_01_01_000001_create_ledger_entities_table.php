<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('ledger.tables.entities', 'ledger_entities'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained(config('ledger.tables.entities', 'ledger_entities'))->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->nullable();
            $table->char('base_currency', 3)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->unique(['parent_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ledger.tables.entities', 'ledger_entities'));
    }
};
