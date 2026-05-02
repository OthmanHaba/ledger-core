<?php

declare(strict_types=1);

namespace LedgerCore\Data;

use LedgerCore\Enums\LineDirection;
use LedgerCore\Support\Decimal;

final readonly class JournalLineData
{
    public function __construct(
        public int|string $accountId,
        public string|LineDirection $direction,
        public string $amount,
        public ?string $currency = null,
        public ?string $baseAmount = null,
        public ?string $exchangeRate = null,
        public ?string $memo = null,
        public array $metadata = [],
    ) {
    }

    public static function debit(
        int|string $accountId,
        string $amount,
        ?string $currency = null,
        ?string $baseAmount = null,
        ?string $exchangeRate = null,
        ?string $memo = null,
        array $metadata = [],
    ): self {
        return new self($accountId, LineDirection::DEBIT, $amount, $currency, $baseAmount, $exchangeRate, $memo, $metadata);
    }

    public static function credit(
        int|string $accountId,
        string $amount,
        ?string $currency = null,
        ?string $baseAmount = null,
        ?string $exchangeRate = null,
        ?string $memo = null,
        array $metadata = [],
    ): self {
        return new self($accountId, LineDirection::CREDIT, $amount, $currency, $baseAmount, $exchangeRate, $memo, $metadata);
    }

    public function direction(): LineDirection
    {
        return $this->direction instanceof LineDirection ? $this->direction : LineDirection::from($this->direction);
    }

    public function amount(): string
    {
        return Decimal::normalize($this->amount);
    }

    public function baseAmount(): ?string
    {
        return $this->baseAmount === null ? null : Decimal::normalize($this->baseAmount);
    }

    public function toPayloadArray(): array
    {
        return [
            'account_id' => (string) $this->accountId,
            'direction' => $this->direction()->value,
            'amount' => $this->amount(),
            'currency' => $this->currency,
            'base_amount' => $this->baseAmount(),
            'exchange_rate' => $this->exchangeRate,
            'memo' => $this->memo,
            'metadata' => $this->metadata,
        ];
    }
}
