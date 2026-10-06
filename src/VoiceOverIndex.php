<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The voice-overs that were found, by speaker and by reference.
 */
final class VoiceOverIndex
{
    /**
     * @var array<string, array<string, VoiceOver>>
     */
    private array $found = [];

    public function __construct(private readonly Duplicates $duplicates) {}

    /**
     * Adds a voice-over, as the way of handling the duplicates says if its reference is already used.
     *
     * @throws ExtractionException when the reference is already used and the duplicates are an error
     */
    public function add(VoiceOver $voiceOver): void
    {
        $known = isset($this->found[$voiceOver->speaker][$voiceOver->reference]);

        if ($known && Duplicates::Error === $this->duplicates) {
            throw ExtractionException::duplicate($voiceOver);
        }

        if (! $known || Duplicates::KeepLast === $this->duplicates) {
            $this->found[$voiceOver->speaker][$voiceOver->reference] = $voiceOver;
        }
    }

    /**
     * @return list<VoiceOver> the voice-overs sorted by speaker, then in the order in which they were added
     */
    public function all(): array
    {
        $found = $this->found;
        ksort($found, SORT_STRING);
        $voiceOvers = [];

        foreach ($found as $references) {
            $voiceOvers = [...$voiceOvers, ...array_values($references)];
        }

        return $voiceOvers;
    }
}
