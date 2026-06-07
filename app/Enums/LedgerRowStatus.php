<?php

namespace App\Enums;

/**
 * The outcome the importer assigns each ledger row before any write.
 */
enum LedgerRowStatus: string
{
    case Import = 'import';
    case SkipZero = 'skip_zero';
    case SkipNegative = 'skip_negative';
    case Duplicate = 'duplicate';
    case Error = 'error';
}
