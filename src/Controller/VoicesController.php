<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Controller;

use AeroFlow\Tts\Voice\Voice;
use AeroFlow\Tts\Voice\VoiceCatalog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class VoicesController
{
    public function __construct(private VoiceCatalog $catalog)
    {
    }

    #[Route('/v1/voices', name: 'voices', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(array_map(
            static fn (Voice $voice): array => $voice->toArray(),
            $this->catalog->all(),
        ));
    }
}
