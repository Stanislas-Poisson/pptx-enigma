<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMElement;
use DOMXPath;
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
    private const NS_DRAWING = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const NS_PRESENTATION = 'http://schemas.openxmlformats.org/presentationml/2006/main';

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
        $notes      = (new NotesReader())->read($this->path);
        $firstSlide = null;
        $sign       = $this->options->sign;

        if (null === $sign) {
            $firstSlide = array_key_first($notes);
            $sign       = null === $firstSlide ? '' : $this->readSign($notes[$firstSlide]);
        }

        if ('' === $sign) {
            throw new ExtractionException('No sign found: the first notes are empty.');
        }

        /** @var array<string, array<string, VoiceOver>> $found */
        $found = [];

        foreach ($notes as $slide => $page) {
            if ($slide === $firstSlide) {
                continue;
            }

            foreach ($this->collect($page, $slide, $sign) as $voiceOver) {
                $found = $this->add($found, $voiceOver);
            }
        }

        ksort($found, SORT_STRING);
        $voiceOvers = [];

        foreach ($found as $references) {
            foreach ($references as $voiceOver) {
                $voiceOvers[] = $voiceOver;
            }
        }

        return new VoiceOvers($sign, $voiceOvers);
    }

    /**
     * @param array<string, array<string, VoiceOver>> $found
     *
     * @return array<string, array<string, VoiceOver>>
     */
    private function add(array $found, VoiceOver $voiceOver): array
    {
        if (isset($found[$voiceOver->speaker][$voiceOver->reference])) {
            if (Duplicates::Error === $this->options->duplicates) {
                throw new ExtractionException(sprintf('The reference "%s" of the voice-over "%s" on slide %d is already used.', $voiceOver->reference, $voiceOver->speaker, $voiceOver->slide));
            }

            if (Duplicates::KeepFirst === $this->options->duplicates) {
                return $found;
            }
        }

        $found[$voiceOver->speaker][$voiceOver->reference] = $voiceOver;

        return $found;
    }

    /**
     * @return list<VoiceOver>
     */
    private function collect(Notes $notes, int $slide, string $sign): array
    {
        $html       = new HtmlRenderer($this->options);
        $text       = new TextRenderer($this->options);
        $opening    = '/^' . preg_quote($sign, '/') . '[^(]*\(([^)]*)\)(.*)$/su';
        $voiceOvers = [];
        $speaker    = null;
        $reference  = '';

        /** @var list<Paragraph> $content */
        $content = [];

        foreach ($this->paragraphs($notes) as $paragraph) {
            if (! str_starts_with($paragraph->trimmedText(), $sign)) {
                $content[] = $paragraph;

                continue;
            }

            if (null !== $speaker) {
                $voiceOvers[] = new VoiceOver($speaker, $reference, $slide, $html->render($content), $text->render($content));
            }

            $speaker = null;
            $content = [];

            if (1 === preg_match($opening, $paragraph->trimmedText(), $marker)) {
                $speaker   = trim($marker[1]);
                $reference = trim($marker[2]);
            }
        }

        if (null !== $speaker) {
            throw new ExtractionException(sprintf('The voice-over "%s" of the reference "%s" on slide %d is not closed.', $speaker, $reference, $slide));
        }

        return $voiceOvers;
    }

    /**
     * The paragraphs of the text of the notes, which is the "body" placeholder of the page.
     *
     * @return list<Paragraph>
     */
    private function paragraphs(Notes $notes): array
    {
        $xpath = new DOMXPath($notes->document);
        $xpath->registerNamespace('p', self::NS_PRESENTATION);
        $xpath->registerNamespace('a', self::NS_DRAWING);
        $reader     = new ParagraphReader($notes->hyperlinks);
        $paragraphs = [];

        foreach ($xpath->query('//p:sp[p:nvSpPr/p:nvPr/p:ph[@type="body"]]/p:txBody/a:p') ?: [] as $paragraph) {
            if ($paragraph instanceof DOMElement) {
                $paragraphs[] = $reader->read($paragraph);
            }
        }

        return $paragraphs;
    }

    private function readSign(Notes $notes): string
    {
        foreach ($this->paragraphs($notes) as $paragraph) {
            if (! $paragraph->isBlank()) {
                return $paragraph->trimmedText();
            }
        }

        return '';
    }
}
