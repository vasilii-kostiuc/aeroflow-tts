<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Voice;

use RuntimeException;

final class UnknownVoiceException extends RuntimeException
{
    public static function forLanguage(string $language): self
    {
        return new self(sprintf('No voice is available for language "%s".', $language));
    }

    public static function forVoice(string $voice, string $language): self
    {
        return new self(sprintf('Voice "%s" is not available for language "%s".', $voice, $language));
    }
}
