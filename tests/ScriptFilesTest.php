<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\ScriptFiles;
use PPTXenigma\ScriptOptions;
use PPTXenigma\VoiceOver;
use PPTXenigma\VoiceOvers;

final class ScriptFilesTest extends TestCase
{
    public function test_names_the_files_after_the_speakers_without_two_of_them_sharing_a_name(): void
    {
        $directory  = sys_get_temp_dir() . '/pptx-enigma-' . bin2hex(random_bytes(4));
        $voiceOvers = new VoiceOvers('¤', array_map(
            static fn (string $speaker): VoiceOver => new VoiceOver($speaker, 'r', 1, '<p>x</p>', 'x'),
            ['Élodie Œuvre', 'Elodie oeuvre', '???', "L'Ogre - n°2", 'Straße'],
        ));

        $paths = (new ScriptFiles())->write($voiceOvers, new ScriptOptions(), $directory, 'text');

        self::assertSame(
            array_map(static fn (string $name): string => $directory . '/' . $name . '.txt', [
                'elodie-oeuvre', 'elodie-oeuvre-2', 'speaker', 'l-ogre-n-2', 'strasse',
            ]),
            $paths,
        );

        array_map(unlink(...), $paths);
        rmdir($directory);
    }
}
