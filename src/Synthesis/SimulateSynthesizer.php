<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Synthesis;

use AeroFlow\Tts\Voice\Voice;

/**
 * Test/CI engine: returns a valid but silent WAV without any external binary or
 * model, so environments with no audio stack (and the automated test suite) can
 * exercise the full HTTP contract. Duration scales with text length so the
 * output looks plausible; it is fully deterministic.
 */
final readonly class SimulateSynthesizer implements Synthesizer
{
    private const SAMPLE_RATE = 22050;
    private const BITS_PER_SAMPLE = 16;
    private const CHANNELS = 1;
    private const BASE_MS = 200;
    private const MS_PER_CHAR = 60;
    private const MAX_MS = 8000;

    public function synthesize(string $text, Voice $voice): string
    {
        $durationMs = min(self::MAX_MS, self::BASE_MS + mb_strlen(trim($text)) * self::MS_PER_CHAR);
        $sampleCount = intdiv(self::SAMPLE_RATE * $durationMs, 1000);
        $dataSize = $sampleCount * self::CHANNELS * intdiv(self::BITS_PER_SAMPLE, 8);

        $byteRate = self::SAMPLE_RATE * self::CHANNELS * intdiv(self::BITS_PER_SAMPLE, 8);
        $blockAlign = self::CHANNELS * intdiv(self::BITS_PER_SAMPLE, 8);

        $header = 'RIFF'
            .pack('V', 36 + $dataSize)
            .'WAVE'
            .'fmt '
            .pack('V', 16)
            .pack('v', 1) // PCM
            .pack('v', self::CHANNELS)
            .pack('V', self::SAMPLE_RATE)
            .pack('V', $byteRate)
            .pack('v', $blockAlign)
            .pack('v', self::BITS_PER_SAMPLE)
            .'data'
            .pack('V', $dataSize);

        return $header.str_repeat("\0", $dataSize);
    }
}
