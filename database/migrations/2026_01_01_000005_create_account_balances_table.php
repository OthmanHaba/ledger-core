<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('ledger.tables.account_balances', 'account_balances'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ledger_account_id')->unique()->constrained(config('ledger.tables.accounts', 'ledger_accounts'))->cascadeOnDelete();
            $table->decimal('debit_total', 24, 8)->default('0');
            $table->decimal('credit_total', 24, 8)->default('0');
            $table->decimal('balance', 24, 8)->default('0');
            $table->char('currency', 3)->nullable();
            $table->foreignId('last_journal_entry_id')->nullable()->constrained(config('ledger.tables.journal_entries', 'journal_entries'))->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ledger.tables.account_balances', 'account_balances'));
    }
};
