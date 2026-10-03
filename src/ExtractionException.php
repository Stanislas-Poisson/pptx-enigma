<?php

declare(strict_types=1);

namespace PPTXenigma;

use RuntimeException;

/**
 * Thrown when a presentation can be read but its notes cannot be extracted.
 */
final class ExtractionException extends RuntimeException {}
