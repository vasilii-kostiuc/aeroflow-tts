<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Voice;

/**
 * A speakable voice: which model speaks, for which language, at which version.
 * `modelVersion` is surfaced to callers so Core can key its generation cache and
 * detect a model upgrade. `modelFile` is a Piper model filename (relative to the
 * models directory); it is irrelevant in simulate mode.
 */
final readonly class Voice
{
    public function __construct(
        public string $name,
        public string $language,
        public string $modelVersion,
        public string $modelFile,
    ) {
    }

    /**
     * @return array{voice: string, language: string, modelVersion: string}
     */
    public function toArray(): array
    {
        return [
            'voice' => $this->name,
            'language' => $this->language,
            'modelVersion' => $this->modelVersion,
        ];
    }
}
