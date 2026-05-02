<?php

declare(strict_types=1);

namespace LedgerCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Exceptions\LedgerException;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => JournalEntryStatus::class,
        'posted_at' => 'immutable_datetime',
        'reversed_at' => 'immutable_datetime',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('ledger.tables.journal_entries', 'journal_entries');
    }

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry): void {
            $entry->uuid ??= (string) Str::uuid();
        });

        static::updating(function (JournalEntry $entry): void {
            if (! $entry->getOriginal('status')) {
                return;
            }

            if ($entry->getOriginal('status') === JournalEntryStatus::DRAFT->value) {
                return;
            }

            $allowed = ['status', 'reversed_at', 'reversed_by_entry_id', 'updated_at'];

            if (config('ledger.posting.allow_posted_metadata_updates', false)) {
                $allowed[] = 'metadata';
            }

            $dirty = array_keys($entry->getDirty());
            $hasOnlyAllowedChanges = empty(array_diff($dirty, $allowed));
            $originalStatus = $entry->getOriginal('status') instanceof JournalEntryStatus
                ? $entry->getOriginal('status')->value
                : $entry->getOriginal('status');

            $currentStatus = $entry->status instanceof JournalEntryStatus
                ? $entry->status->value
                : $entry->status;

            $isMarkingReversed = $originalStatus === JournalEntryStatus::POSTED->value
                && $currentStatus === JournalEntryStatus::REVERSED->value;

            if (! $hasOnlyAllowedChanges || (! $isMarkingReversed && $entry->isDirty('status'))) {
                throw new LedgerException('Posted journal entries are immutable. Create a reversal instead.');
            }
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(config('ledger.models.entity', LedgerEntity::class), 'ledger_entity_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(config('ledger.models.journal_line', JournalLine::class), 'journal_entry_id');
    }

    public function reversalEntry(): BelongsTo
    {
        return $this->belongsTo(static::class, 'reversed_by_entry_id');
    }
}
