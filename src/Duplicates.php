<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * What to do when a speaker uses the same reference twice.
 */
enum Duplicates: string
{
    case Error = 'error';
    case KeepFirst = 'first';
    case KeepLast = 'last';
}
