<?php

declare(strict_types=1);

namespace LedgerCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LedgerEntity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('ledger.tables.entities', 'ledger_entities');
    }

    protected static function booted(): void
    {
        static::creating(function (LedgerEntity $entity): void {
            $entity->uuid ??= (string) Str::uuid();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(config('ledger.models.account', LedgerAccount::class), 'ledger_entity_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(config('ledger.models.journal_entry', JournalEntry::class), 'ledger_entity_id');
    }
}
