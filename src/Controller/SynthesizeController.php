<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Controller;

use AeroFlow\Tts\Synthesis\SynthesisException;
use AeroFlow\Tts\Synthesis\Synthesizer;
use AeroFlow\Tts\Voice\UnknownVoiceException;
use AeroFlow\Tts\Voice\VoiceCatalog;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The single meaningful endpoint: text -> WAV. Neutral to the airport domain —
 * it only ever sees text, language and voice.
 */
final readonly class SynthesizeController
{
    private const MAX_TEXT_LENGTH = 2000;

    public function __construct(
        private VoiceCatalog $catalog,
        private Synthesizer $synthesizer,
    ) {
    }

    #[Route('/v1/synthesize', name: 'synthesize', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $payload = json_decode($request->getContent(), true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->error('Request body must be valid JSON.', Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($payload)) {
            return $this->error('Request body must be a JSON object.', Response::HTTP_BAD_REQUEST);
        }

        $text = is_string($payload['text'] ?? null) ? trim($payload['text']) : '';
        $language = is_string($payload['language'] ?? null) ? trim($payload['language']) : '';
        $voiceName = is_string($payload['voice'] ?? null) && '' !== trim($payload['voice']) ? trim($payload['voice']) : null;

        if ('' === $text) {
            return $this->error('Field "text" is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            return $this->error(sprintf('Field "text" cannot exceed %d characters.', self::MAX_TEXT_LENGTH), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ('' === $language) {
            return $this->error('Field "language" is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $voice = $this->catalog->resolve($language, $voiceName);
        } catch (UnknownVoiceException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $wav = $this->synthesizer->synthesize($text, $voice);
        } catch (SynthesisException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_BAD_GATEWAY);
        }

        return new Response($wav, Response::HTTP_OK, [
            'Content-Type' => 'audio/wav',
            'X-TTS-Model-Version' => $voice->modelVersion,
        ]);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
