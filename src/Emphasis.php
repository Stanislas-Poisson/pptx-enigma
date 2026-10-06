<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * How the styles of the notes are written in a plain text, where there is no bold or italic.
 */
enum Emphasis: string
{
    /**
     * Bold between "*", italic between "_" and underline between "__".
     */
    case Marks = 'marks';

    /**
     * The styles are dropped.
     */
    case None = 'none';

    /**
     * The bold text is written in uppercase, the other styles are dropped.
     */
    case Upper = 'upper';
}
