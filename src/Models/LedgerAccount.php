<?php

declare(strict_types=1);

namespace LedgerCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LedgerCore\Enums\AccountType;
use LedgerCore\Enums\NormalBalance;
use LedgerCore\Exceptions\LedgerException;

class LedgerAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'type' => AccountType::class,
        'normal_balance' => NormalBalance::class,
        'metadata' => 'array',
        'is_control_account' => 'boolean',
        'is_postable' => 'boolean',
        'allow_negative' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('ledger.tables.accounts', 'ledger_accounts');
    }

    protected static function booted(): void
    {
        static::creating(function (LedgerAccount $account): void {
            $account->uuid ??= (string) Str::uuid();
        });

        static::updating(function (LedgerAccount $account): void {
            $dangerous = ['type', 'normal_balance', 'currency'];

            if ($account->isDirty($dangerous) && $account->journalLines()->exists()) {
                throw new LedgerException('Account type, normal balance, and currency cannot be changed after posting.');
            }
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(config('ledger.models.entity', LedgerEntity::class), 'ledger_entity_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(config('ledger.models.journal_line', JournalLine::class), 'ledger_account_id');
    }

    public function balance(): HasOne
    {
        return $this->hasOne(config('ledger.models.account_balance', AccountBalance::class), 'ledger_account_id');
    }
}
