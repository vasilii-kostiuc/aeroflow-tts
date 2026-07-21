<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Synthesis;

use AeroFlow\Tts\Voice\Voice;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Real engine: shells out to the Piper binary, which reads text on stdin and
 * writes a WAV to stdout. Offline and native — no Python runtime. The binary and
 * model files must be present on the host/image; paths come from configuration.
 */
final readonly class PiperSynthesizer implements Synthesizer
{
    public function __construct(
        private string $binaryPath,
        private string $modelsDirectory,
        private float $timeoutSeconds = 30.0,
    ) {
    }

    public function synthesize(string $text, Voice $voice): string
    {
        $process = new Process([
            $this->binaryPath,
            '--model', rtrim($this->modelsDirectory, '/').'/'.$voice->modelFile,
            '--output_file', '-',
        ]);
        $process->setInput($text);
        $process->setTimeout($this->timeoutSeconds);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw SynthesisException::engineFailed(trim($process->getErrorOutput()) ?: $exception->getMessage(), $exception);
        }

        $wav = $process->getOutput();
        if ('' === $wav) {
            throw SynthesisException::engineFailed('piper produced no audio');
        }

        return $wav;
    }
}
