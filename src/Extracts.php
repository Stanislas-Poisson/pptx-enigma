<?php

declare(strict_types=1);

namespace PPTXenigma;

use InvalidArgumentException;

/**
 * Extracts the voice-over texts written in the notes of a presentation.
 *
 * In a note, a voice-over starts with a line that begins with the sign, then
 * the speaker between parentheses, then the reference. It ends at the next
 * line that begins with the sign:
 *
 *     ¤ VOICE OVER (Narrator) intro_01
 *     The text of the voice-over.
 *     ¤ VOICE OVER END
 */
final readonly class Extracts
{
    public function __construct(
        private string $path,
        private Options $options = new Options(),
    ) {}

    /**
     * Without a sign in the options, the sign is the text of the first notes of the
     * presentation, which are not searched for voice-overs.
     *
     * @throws InvalidArgumentException when the file is not a PowerPoint 2007+ presentation
     * @throws ExtractionException      when the sign cannot be found, a reference is used twice (unless the
     *                                  options keep one of them) or a voice-over is not closed
     */
    public function extract(): VoiceOvers
    {
        $notes                         = (new NotesReader())->read($this->path);
        [$sign, $signedSlide]          = $this->sign($notes);
        $htmlRenderer                  = new HtmlRenderer($this->options);
        $textRenderer                  = new TextRenderer($this->options);
        $marksRenderer                 = new TextRenderer($this->options, Emphasis::Marks);
        $upperRenderer                 = new TextRenderer($this->options, Emphasis::Upper);
        $voiceOverIndex                = new VoiceOverIndex($this->options->duplicates);
        $markers                       = new Markers($sign, $this->options->endSign);

        foreach (array_diff_key($notes, [$signedSlide => true]) as $slide => $note) {
            $reader = new VoiceOverReader(
                $markers,
                $slide,
                $htmlRenderer,
                $textRenderer,
                $marksRenderer,
                $upperRenderer,
            );

            foreach ($reader->read($note->paragraphs()) as $voiceOver) {
                $voiceOverIndex->add($voiceOver);
            }
        }

        return new VoiceOvers($sign, $voiceOverIndex->all());
    }

    private function readSign(Notes $notes): string
    {
        foreach ($notes->paragraphs() as $paragraph) {
            if (! $paragraph->isBlank()) {
                return $paragraph->trimmedText();
            }
        }

        return '';
    }

    /**
     * @param array<int, Notes> $notes
     *
     * @return array{string, int|null} the sign, and the slide whose notes hold only the sign, if there is one
     *
     * @throws ExtractionException when the sign is not set and the first notes are empty
     */
    private function sign(array $notes): array
    {
        if (null !== $this->options->sign) {
            return [$this->options->sign, null];
        }

        $firstSlide = array_key_first($notes);
        $sign       = null === $firstSlide ? '' : $this->readSign($notes[$firstSlide]);

        return '' === $sign ? throw ExtractionException::noSign() : [$sign, $firstSlide];
    }
}
