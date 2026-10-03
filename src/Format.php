<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The content of a voice-over that is written in a result.
 */
enum Format: string
{
    case Html = 'html';
    case Text = 'text';
}
