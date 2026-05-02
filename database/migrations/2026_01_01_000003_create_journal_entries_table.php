<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('ledger.tables.journal_entries', 'journal_entries'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ledger_entity_id')->constrained(config('ledger.tables.entities', 'ledger_entities'))->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('payload_hash')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained(config('ledger.tables.journal_entries', 'journal_entries'))->nullOnDelete();
            $table->string('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('ledger_entity_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ledger.tables.journal_entries', 'journal_entries'));
    }
};
