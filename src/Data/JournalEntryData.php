<?php

declare(strict_types=1);

namespace LedgerCore\Data;

use DateTimeInterface;

final readonly class JournalEntryData
{
    /**
     * @param array<int, JournalLineData> $lines
     */
    public function __construct(
        public int|string $ledgerEntityId,
        public string $idempotencyKey,
        public array $lines,
        public ?string $referenceType = null,
        public int|string|null $referenceId = null,
        public ?string $description = null,
        public array $metadata = [],
        public ?DateTimeInterface $postedAt = null,
        public int|string|null $createdBy = null,
    ) {
    }

    public function payloadHash(): string
    {
        return hash('sha256', json_encode($this->toPayloadArray(), JSON_THROW_ON_ERROR));
    }

    public function toPayloadArray(): array
    {
        return [
            'ledger_entity_id' => (string) $this->ledgerEntityId,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId === null ? null : (string) $this->referenceId,
            'description' => $this->description,
            'lines' => array_map(
                static fn (JournalLineData $line): array => $line->toPayloadArray(),
                $this->lines,
            ),
            'metadata' => $this->metadata,
            'posted_at' => $this->postedAt?->format(DateTimeInterface::ATOM),
        ];
    }
}
