<?php

declare(strict_types=1);

namespace LedgerCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LedgerCore\Models\JournalEntry;

final class JournalEntryReversed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public JournalEntry $original,
        public JournalEntry $reversal,
    ) {
    }
}
