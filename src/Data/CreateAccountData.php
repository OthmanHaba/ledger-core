<?php

declare(strict_types=1);

namespace LedgerCore\Data;

use LedgerCore\Enums\AccountType;
use LedgerCore\Enums\NormalBalance;

final readonly class CreateAccountData
{
    public function __construct(
        public int|string $ledgerEntityId,
        public string $name,
        public string|AccountType $type,
        public string|NormalBalance $normalBalance,
        public ?string $code = null,
        public ?string $currency = null,
        public int|string|null $parentId = null,
        public ?string $counterpartyType = null,
        public int|string|null $counterpartyId = null,
        public bool $isControlAccount = false,
        public bool $isPostable = true,
        public bool $allowNegative = false,
        public array $metadata = [],
    ) {
    }

    public function type(): AccountType
    {
        return $this->type instanceof AccountType ? $this->type : AccountType::from($this->type);
    }

    public function normalBalance(): NormalBalance
    {
        return $this->normalBalance instanceof NormalBalance ? $this->normalBalance : NormalBalance::from($this->normalBalance);
    }
}
