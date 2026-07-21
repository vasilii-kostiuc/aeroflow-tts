<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Tests\Unit\Controller;

use AeroFlow\Tts\Controller\SynthesizeController;
use AeroFlow\Tts\Synthesis\SimulateSynthesizer;
use AeroFlow\Tts\Synthesis\SynthesisException;
use AeroFlow\Tts\Synthesis\Synthesizer;
use AeroFlow\Tts\Voice\Voice;
use AeroFlow\Tts\Voice\VoiceCatalog;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SynthesizeControllerTest extends TestCase
{
    public function testEngineFailureMapsToBadGateway(): void
    {
        $failing = new class implements Synthesizer {
            public function synthesize(string $text, Voice $voice): string
            {
                throw SynthesisException::engineFailed('boom');
            }
        };

        $response = new SynthesizeController($this->catalog(), $failing)(
            $this->request(['text' => 'hello', 'language' => 'ru']),
        );

        self::assertSame(502, $response->getStatusCode());
    }

    public function testSuccessfulSynthesisSetsWavHeaders(): void
    {
        $response = new SynthesizeController($this->catalog(), new SimulateSynthesizer())(
            $this->request(['text' => 'hello', 'language' => 'ru']),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('audio/wav', $response->headers->get('Content-Type'));
        self::assertSame('piper-ru-1', $response->headers->get('X-TTS-Model-Version'));
    }

    private function catalog(): VoiceCatalog
    {
        return VoiceCatalog::fromDefinitions([
            ['name' => 'dmitri', 'language' => 'ru', 'modelVersion' => 'piper-ru-1', 'modelFile' => 'ru.onnx'],
        ]);
    }

    /**
     * @param array<string, string> $payload
     */
    private function request(array $payload): Request
    {
        return Request::create(
            '/v1/synthesize',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
