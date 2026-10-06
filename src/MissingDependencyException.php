<?php

declare(strict_types=1);

namespace PPTXenigma;

use RuntimeException;

/**
 * A package that is optional is needed and is not installed.
 */
final class MissingDependencyException extends RuntimeException
{
    public static function pdf(): self
    {
        return new self('A PDF needs the package dompdf: run "composer require dompdf/dompdf".');
    }
}
