<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Where the reference of a voice-over is written in a script.
 */
enum Layout: string
{
    /**
     * The reference on a line above the text.
     */
    case ReferenceAbove = 'above';

    /**
     * The reference on a line below the text.
     */
    case ReferenceBelow = 'below';

    /**
     * The reference at the start of the first line of the text, between brackets.
     */
    case ReferenceInline = 'inline';

    /**
     * Only the text, without the reference.
     */
    case TextOnly = 'none';
}
