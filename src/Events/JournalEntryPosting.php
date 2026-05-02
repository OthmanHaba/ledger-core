<?php

declare(strict_types=1);

namespace LedgerCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LedgerCore\Data\JournalEntryData;

final class JournalEntryPosting
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public JournalEntryData $data)
    {
    }
}
