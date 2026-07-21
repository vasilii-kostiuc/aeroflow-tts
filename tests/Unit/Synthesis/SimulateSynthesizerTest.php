<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Tests\Unit\Synthesis;

use AeroFlow\Tts\Synthesis\SimulateSynthesizer;
use AeroFlow\Tts\Voice\Voice;
use PHPUnit\Framework\TestCase;

final class SimulateSynthesizerTest extends TestCase
{
    public function testProducesValidWavHeader(): void
    {
        $wav = new SimulateSynthesizer()->synthesize('hello', new Voice('dmitri', 'ru', 'v1', 'ru.onnx'));

        self::assertStringStartsWith('RIFF', $wav);
        self::assertSame('WAVE', substr($wav, 8, 4));
        self::assertSame('data', substr($wav, 36, 4));
    }

    public function testIsDeterministic(): void
    {
        $voice = new Voice('dmitri', 'ru', 'v1', 'ru.onnx');
        $synth = new SimulateSynthesizer();

        self::assertSame($synth->synthesize('same text', $voice), $synth->synthesize('same text', $voice));
    }

    public function testLongerTextYieldsLargerAudio(): void
    {
        $voice = new Voice('dmitri', 'ru', 'v1', 'ru.onnx');
        $synth = new SimulateSynthesizer();

        self::assertGreaterThan(
            strlen($synth->synthesize('hi', $voice)),
            strlen($synth->synthesize(str_repeat('word ', 20), $voice)),
        );
    }
}
