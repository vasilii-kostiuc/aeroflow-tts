<?php

declare(strict_types=1);

namespace AeroFlow\Tts\Voice;

/**
 * The set of voices this service can speak. Stateless configuration, not a
 * database. The default voice for a language is the first one listed for it.
 */
final readonly class VoiceCatalog
{
    /**
     * @var list<Voice>
     */
    private array $voices;

    /**
     * @param iterable<Voice> $voices
     */
    public function __construct(iterable $voices)
    {
        $this->voices = array_values([...$voices]);
    }

    /**
     * @param list<array{name: string, language: string, modelVersion: string, modelFile: string}> $definitions
     */
    public static function fromDefinitions(array $definitions): self
    {
        return new self(array_map(
            static fn (array $d): Voice => new Voice($d['name'], $d['language'], $d['modelVersion'], $d['modelFile']),
            $definitions,
        ));
    }

    /**
     * @return list<Voice>
     */
    public function all(): array
    {
        return $this->voices;
    }

    /**
     * @throws UnknownVoiceException when no voice matches the language (and voice, if given)
     */
    public function resolve(string $language, ?string $voice): Voice
    {
        foreach ($this->voices as $candidate) {
            if ($candidate->language !== $language) {
                continue;
            }
            if (null === $voice) {
                return $candidate; // default = first listed for the language
            }
            if ($candidate->name === $voice) {
                return $candidate;
            }
        }

        throw null !== $voice ? UnknownVoiceException::forVoice($voice, $language) : UnknownVoiceException::forLanguage($language);
    }
}
