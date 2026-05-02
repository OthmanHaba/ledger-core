<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LedgerCore\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
