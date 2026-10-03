<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Reads the voice-overs of the paragraphs of one page of notes: a voice-over starts at a line that begins
 * with the sign and has a speaker, and ends at the next line that begins with the sign.
 */
final class VoiceOverReader
{
    /**
     * @var list<Paragraph>
     */
    private array $content = [];

    private string $reference = '';

    private ?string $speaker = null;

    /**
     * @var list<VoiceOver>
     */
    private array $voiceOvers = [];

    public function __construct(
        private readonly string $sign,
        private readonly int $slide,
        private readonly HtmlRenderer $htmlRenderer,
        private readonly TextRenderer $textRenderer,
    ) {}

    /**
     * @param list<Paragraph> $paragraphs
     *
     * @return list<VoiceOver>
     *
     * @throws ExtractionException when a voice-over is not closed
     */
    public function read(array $paragraphs): array
    {
        foreach ($paragraphs as $paragraph) {
            $this->accept($paragraph);
        }

        if (null !== $this->speaker) {
            throw ExtractionException::notClosed($this->speaker, $this->reference, $this->slide);
        }

        return $this->voiceOvers;
    }

    private function accept(Paragraph $paragraph): void
    {
        $line = $paragraph->trimmedText();

        if (! str_starts_with($line, $this->sign)) {
            $this->content[] = $paragraph;

            return;
        }

        $this->close();
        $this->open($line);
    }

    private function close(): void
    {
        if (null !== $this->speaker) {
            $this->voiceOvers[] = new VoiceOver(
                $this->speaker,
                $this->reference,
                $this->slide,
                $this->htmlRenderer->render($this->content),
                $this->textRenderer->render($this->content),
            );
        }

        $this->speaker = null;
        $this->content = [];
    }

    private function open(string $line): void
    {
        if (1 === preg_match('/^' . preg_quote($this->sign, '/') . '[^(]*\(([^)]*)\)(.*)$/su', $line, $marker)) {
            $this->speaker   = trim($marker[1]);
            $this->reference = trim($marker[2]);
        }
    }
}
