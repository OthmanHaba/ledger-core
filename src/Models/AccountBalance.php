<?php

declare(strict_types=1);

namespace LedgerCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'debit_total' => 'decimal:8',
        'credit_total' => 'decimal:8',
        'balance' => 'decimal:8',
    ];

    public function getTable(): string
    {
        return config('ledger.tables.account_balances', 'account_balances');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(config('ledger.models.account', LedgerAccount::class), 'ledger_account_id');
    }

    public function lastJournalEntry(): BelongsTo
    {
        return $this->belongsTo(config('ledger.models.journal_entry', JournalEntry::class), 'last_journal_entry_id');
    }
}
