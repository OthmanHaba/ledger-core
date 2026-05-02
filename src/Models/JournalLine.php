<?php

declare(strict_types=1);

namespace LedgerCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Enums\LineDirection;
use LedgerCore\Exceptions\LedgerException;
use Illuminate\Support\Str;

class JournalLine extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'direction' => LineDirection::class,
        'amount' => 'decimal:8',
        'base_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:12',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('ledger.tables.journal_lines', 'journal_lines');
    }

    protected static function booted(): void
    {
        static::creating(function (JournalLine $line): void {
            $line->uuid ??= (string) Str::uuid();
        });

        static::updating(fn (JournalLine $line) => $line->guardPostedMutation());
        static::deleting(fn (JournalLine $line) => $line->guardPostedMutation());
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(config('ledger.models.journal_entry', JournalEntry::class), 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(config('ledger.models.account', LedgerAccount::class), 'ledger_account_id');
    }

    private function guardPostedMutation(): void
    {
        $status = $this->entry?->status;

        if ($status === JournalEntryStatus::POSTED || $status === JournalEntryStatus::REVERSED) {
            throw new LedgerException('Posted journal lines are immutable.');
        }
    }
}
