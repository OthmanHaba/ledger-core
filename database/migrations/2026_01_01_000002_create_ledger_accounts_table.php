<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('ledger.tables.accounts', 'ledger_accounts'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ledger_entity_id')->constrained(config('ledger.tables.entities', 'ledger_entities'))->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained(config('ledger.tables.accounts', 'ledger_accounts'))->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('type');
            $table->string('normal_balance');
            $table->char('currency', 3)->nullable();
            $table->string('counterparty_type')->nullable();
            $table->string('counterparty_id')->nullable();
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_postable')->default(true);
            $table->boolean('allow_negative')->default(false);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('ledger_entity_id');
            $table->index('type');
            $table->index('currency');
            $table->index(['counterparty_type', 'counterparty_id']);
            $table->unique(['ledger_entity_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ledger.tables.accounts', 'ledger_accounts'));
    }
};
