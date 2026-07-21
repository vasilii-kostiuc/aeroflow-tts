<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Synthesis;

use RuntimeException;
use Throwable;

final class SynthesisException extends RuntimeException
{
    public static function engineFailed(string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Speech synthesis failed: %s', $reason), 0, $previous);
    }
}
