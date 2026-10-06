<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The signs that delimit the voice-overs: the one that opens them, and the one that closes them.
 */
final readonly class Markers
{
    /**
     * @param string      $sign    the sign that opens a voice-over
     * @param string|null $endSign the sign that closes it; without it, the sign that opens it
     */
    public function __construct(
        private string $sign,
        private ?string $endSign = null,
    ) {}

    /**
     * Only the closing sign closes a voice-over when the two signs are different. With one sign, any line that
     * starts with it closes the voice-over, and can open the next one.
     */
    public function closesOnly(string $line): bool
    {
        return $this->endSign() !== $this->sign && str_starts_with($line, $this->endSign());
    }

    public function isMarker(string $line): bool
    {
        return str_starts_with($line, $this->sign) || str_starts_with($line, $this->endSign());
    }

    public function sign(): string
    {
        return $this->sign;
    }

    private function endSign(): string
    {
        return $this->endSign ?? $this->sign;
    }
}
