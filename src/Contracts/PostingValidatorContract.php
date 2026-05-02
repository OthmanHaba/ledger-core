<?php

declare(strict_types=1);

namespace LedgerCore\Contracts;

use LedgerCore\Data\JournalEntryData;

interface PostingValidatorContract
{
    public function validate(JournalEntryData $entry): void;
}
