<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Extracts the voice-over texts written in the notes of a presentation,
 * grouped by speaker and by reference.
 *
 * In a note, a voice-over starts with a line that begins with the sign, then
 * the speaker between parentheses, then the reference. It ends at the next
 * line that begins with the sign:
 *
 *     ¤ VOICE OVER (Narrator) intro_01
 *     The text of the voice-over.
 *     ¤ VOICE OVER END
 */
final class Extracts
{
    private const NS_DRAWING = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    private const NS_PRESENTATION = 'http://schemas.openxmlformats.org/presentationml/2006/main';

    private ?string $sign = null;

    private bool $signIsSet = false;

    /**
     * @var array<string, array<string, string>>
     */
    private array $voiceOvers = [];

    public function __construct(private readonly string $path)
    {
    }

    /**
     * Sets the sign instead of reading it in the first notes.
     */
    public function setSign(string $sign): self
    {
        $this->sign = $sign;
        $this->signIsSet = '' !== $sign;

        return $this;
    }

    public function getSign(): ?string
    {
        return $this->sign;
    }

    /**
     * Without a sign set before, the sign is the text of the first notes of the
     * presentation, which are not searched for voice-overs.
     *
     * @throws \InvalidArgumentException when the file is not a PowerPoint 2007+ presentation
     * @throws ExtractionException       when the sign cannot be found, a reference is used twice or a voice-over is not closed
     */
    public function extract(): self
    {
        $notes = (new NotesReader())->read($this->path);
        $this->voiceOvers = [];
        $firstSlide = null;

        if ($this->signIsSet) {
            $sign = $this->sign ?? '';
        } else {
            $firstSlide = array_key_first($notes);
            $sign = null === $firstSlide ? '' : $this->readSign($notes[$firstSlide]->document);
            $this->sign = '' === $sign ? null : $sign;
        }

        if ('' === $sign) {
            throw new ExtractionException('No sign found: the first notes are empty.');
        }

        foreach ($notes as $slide => $page) {
            if ($slide !== $firstSlide) {
                $this->collect($page, $slide, $sign);
            }
        }

        return $this;
    }

    /**
     * @return array<string, array<string, string>> the HTML of each voice-over, by speaker then by reference, sorted by speaker
     */
    public function getVoiceOver(): array
    {
        $voiceOvers = $this->voiceOvers;
        ksort($voiceOvers, SORT_STRING);

        return $voiceOvers;
    }

    private function readSign(\DOMDocument $notes): string
    {
        $converter = new ParagraphConverter();

        foreach ($this->paragraphs($notes) as $paragraph) {
            $text = $converter->trimmedText($paragraph);

            if ('' !== $text) {
                return $text;
            }
        }

        return '';
    }

    private function collect(Notes $notes, int $slide, string $sign): void
    {
        $converter = new ParagraphConverter($notes->hyperlinks);
        $opening = '/^' . preg_quote($sign, '/') . '[^(]*\(([^)]*)\)(.*)$/su';
        $speaker = null;
        $reference = '';
        /** @var list<\DOMElement> $content */
        $content = [];

        foreach ($this->paragraphs($notes->document) as $paragraph) {
            $text = $converter->trimmedText($paragraph);

            if (!str_starts_with($text, $sign)) {
                $content[] = $paragraph;

                continue;
            }

            if (null !== $speaker) {
                $this->store($speaker, $reference, $converter->convert($content), $slide);
            }

            $speaker = null;
            $content = [];

            if (1 === preg_match($opening, $text, $marker)) {
                $speaker = trim($marker[1]);
                $reference = trim($marker[2]);
            }
        }

        if (null !== $speaker) {
            throw new ExtractionException(sprintf('The voice-over "%s" of the reference "%s" on slide %d is not closed.', $speaker, $reference, $slide));
        }
    }

    private function store(string $speaker, string $reference, string $html, int $slide): void
    {
        if (isset($this->voiceOvers[$speaker][$reference])) {
            throw new ExtractionException(sprintf('The reference "%s" of the voice-over "%s" on slide %d is already used.', $reference, $speaker, $slide));
        }

        $this->voiceOvers[$speaker][$reference] = $html;
    }

    /**
     * The paragraphs of the text of the notes, which is the "body" placeholder of the page.
     *
     * @return list<\DOMElement>
     */
    private function paragraphs(\DOMDocument $notes): array
    {
        $xpath = new \DOMXPath($notes);
        $xpath->registerNamespace('p', self::NS_PRESENTATION);
        $xpath->registerNamespace('a', self::NS_DRAWING);
        $paragraphs = [];

        foreach ($xpath->query('//p:sp[p:nvSpPr/p:nvPr/p:ph[@type="body"]]/p:txBody/a:p') ?: [] as $paragraph) {
            if ($paragraph instanceof \DOMElement) {
                $paragraphs[] = $paragraph;
            }
        }

        return $paragraphs;
    }
}
