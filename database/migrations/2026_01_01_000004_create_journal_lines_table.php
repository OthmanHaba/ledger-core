<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('ledger.tables.journal_lines', 'journal_lines'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('journal_entry_id')->constrained(config('ledger.tables.journal_entries', 'journal_entries'))->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained(config('ledger.tables.accounts', 'ledger_accounts'))->restrictOnDelete();
            $table->string('direction');
            $table->decimal('amount', 24, 8);
            $table->char('currency', 3)->nullable();
            $table->decimal('base_amount', 24, 8)->nullable();
            $table->decimal('exchange_rate', 24, 12)->nullable();
            $table->text('memo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('ledger_account_id');
            $table->index('direction');
            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ledger.tables.journal_lines', 'journal_lines'));
    }
};
