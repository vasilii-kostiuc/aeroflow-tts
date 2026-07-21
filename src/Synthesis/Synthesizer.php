<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Synthesis;

use AeroFlow\Tts\Voice\Voice;

/**
 * Turns text into WAV bytes with a chosen voice. The engine behind this
 * interface (Piper, simulate, or a future one) is an implementation detail; the
 * HTTP contract never changes when it is swapped.
 */
interface Synthesizer
{
    /**
     * @return string raw WAV bytes
     *
     * @throws SynthesisException when the engine fails to produce audio
     */
    public function synthesize(string $text, Voice $voice): string;
}
