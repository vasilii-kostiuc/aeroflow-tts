<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Drives the service through the real kernel and DI (simulate engine in the test
 * environment), exercising routing, the voices catalog and the synthesize
 * contract end to end.
 */
final class SynthesisApiTest extends WebTestCase
{
    public function testHealth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertJson((string) $client->getResponse()->getContent());
    }

    public function testVoicesListsConfiguredCatalog(): void
    {
        $client = static::createClient();
        $client->request('GET', '/v1/voices');

        self::assertResponseIsSuccessful();
        $voices = json_decode((string) $client->getResponse()->getContent(), true, 8, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($voices);
        self::assertArrayHasKey('voice', $voices[0]);
        self::assertArrayHasKey('language', $voices[0]);
        self::assertArrayHasKey('modelVersion', $voices[0]);
    }

    public function testSynthesizeReturnsWavWithModelVersionHeader(): void
    {
        $client = static::createClient();
        $this->postSynthesize($client, ['text' => 'Рейс 214 приглашается на посадку', 'language' => 'ru']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'audio/wav');
        self::assertNotEmpty($client->getResponse()->headers->get('X-TTS-Model-Version'));
        self::assertStringStartsWith('RIFF', (string) $client->getResponse()->getContent());
    }

    public function testSynthesizeRejectsEmptyText(): void
    {
        $client = static::createClient();
        $this->postSynthesize($client, ['text' => '   ', 'language' => 'ru']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testSynthesizeRejectsUnknownLanguage(): void
    {
        $client = static::createClient();
        $this->postSynthesize($client, ['text' => 'hello', 'language' => 'de']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testSynthesizeRejectsInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/v1/synthesize', server: ['CONTENT_TYPE' => 'application/json'], content: '{not json');

        self::assertResponseStatusCodeSame(400);
    }

    public function testUnknownRouteGives404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nope');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, string> $payload
     */
    private function postSynthesize(KernelBrowser $client, array $payload): void
    {
        $client->request(
            'POST',
            '/v1/synthesize',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
