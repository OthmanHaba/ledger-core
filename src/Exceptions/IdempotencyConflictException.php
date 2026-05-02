<?php

declare(strict_types=1);

namespace LedgerCore\Exceptions;

class IdempotencyConflictException extends DuplicateJournalEntryException
{
}
