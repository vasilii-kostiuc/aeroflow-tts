<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Synthesis;

/**
 * Chooses the synthesis engine from configuration. `simulate` (default) needs no
 * binary or model and is used in CI and on audio-less hosts; `piper` performs
 * real offline synthesis. Swapping engines never changes the HTTP contract.
 */
final readonly class SynthesizerFactory
{
    public function __construct(
        private string $mode,
        private string $piperBinary,
        private string $modelsDirectory,
        private float $timeoutSeconds,
    ) {
    }

    public function create(): Synthesizer
    {
        if ('piper' === $this->mode) {
            return new PiperSynthesizer($this->piperBinary, $this->modelsDirectory, $this->timeoutSeconds);
        }

        return new SimulateSynthesizer();
    }
}
