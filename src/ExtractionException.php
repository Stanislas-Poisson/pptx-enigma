<?php

declare(strict_types=1);

namespace PPTXenigma;

use RuntimeException;

/**
 * Thrown when a presentation can be read but its notes cannot be extracted.
 */
final class ExtractionException extends RuntimeException
{
    public static function duplicate(VoiceOver $voiceOver): self
    {
        return new self(sprintf(
            'The reference "%s" of the voice-over "%s" on slide %d is already used.',
            $voiceOver->reference,
            $voiceOver->speaker,
            $voiceOver->slide,
        ));
    }

    public static function noSign(): self
    {
        return new self('No sign found: the first notes are empty.');
    }

    public static function notClosed(string $speaker, string $reference, int $slide): self
    {
        return new self(sprintf(
            'The voice-over "%s" of the reference "%s" on slide %d is not closed.',
            $speaker,
            $reference,
            $slide,
        ));
    }
}
