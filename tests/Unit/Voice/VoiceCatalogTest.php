<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Tests\Unit\Voice;

use AeroFlow\Tts\Voice\UnknownVoiceException;
use AeroFlow\Tts\Voice\VoiceCatalog;
use PHPUnit\Framework\TestCase;

final class VoiceCatalogTest extends TestCase
{
    public function testResolvesDefaultVoiceAsFirstListedForLanguage(): void
    {
        $voice = $this->catalog()->resolve('ru', null);

        self::assertSame('dmitri', $voice->name);
        self::assertSame('piper-ru-1', $voice->modelVersion);
    }

    public function testResolvesSpecificVoice(): void
    {
        $voice = $this->catalog()->resolve('ru', 'irina');

        self::assertSame('irina', $voice->name);
    }

    public function testRejectsUnknownLanguage(): void
    {
        $this->expectException(UnknownVoiceException::class);
        $this->catalog()->resolve('de', null);
    }

    public function testRejectsUnknownVoiceForKnownLanguage(): void
    {
        $this->expectException(UnknownVoiceException::class);
        $this->catalog()->resolve('ru', 'nope');
    }

    private function catalog(): VoiceCatalog
    {
        return VoiceCatalog::fromDefinitions([
            ['name' => 'dmitri', 'language' => 'ru', 'modelVersion' => 'piper-ru-1', 'modelFile' => 'ru.onnx'],
            ['name' => 'irina', 'language' => 'ru', 'modelVersion' => 'piper-ru-irina-1', 'modelFile' => 'ru-irina.onnx'],
            ['name' => 'lessac', 'language' => 'en', 'modelVersion' => 'piper-en-1', 'modelFile' => 'en.onnx'],
        ]);
    }
}
