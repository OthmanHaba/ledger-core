<?php

declare(strict_types=1);

namespace LedgerCore\Support;

use LedgerCore\Contracts\PostingValidatorContract;
use LedgerCore\Data\JournalEntryData;

final class NullPostingValidator implements PostingValidatorContract
{
    public function validate(JournalEntryData $entry): void
    {
    }
}
