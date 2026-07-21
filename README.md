# aeroflow-tts

Stateless text-to-speech service for AeroFlow. It turns text into a WAV and
nothing more — it is **neutral to the airport domain**: it knows only `text`,
`language` and `voice`, never announcements, flights, priorities, gates or
counters. This keeps it reusable and stops it from becoming a hidden part of
Audio Catalog.

Architecturally it is an **infrastructure service in the role of Audio Catalog**,
not a new bounded context: no database, no domain invariants, no airport model.
`aeroflow-core` calls it synchronously over HTTP (via `TextToSpeechPort`) as a
pre-step when the text of an announcement configuration is saved, then stores the
result as `AudioAsset(source=generated)`. The generation **cache and
idempotency live in Core**, not here. See `aeroflow-docs/domain-model.md`
("Генерация assets через TTS") and task 021.

Built on Symfony framework-bundle, mirroring `aeroflow-core`'s layout (Kernel,
DI container, attribute-routed controllers) — minus Doctrine, Messenger and
security, which a stateless HTTP wrapper does not need.

## HTTP contract

```text
GET  /health                                   -> 200 {"status":"ok"}
GET  /v1/voices                                -> 200 [{voice, language, modelVersion}, ...]
POST /v1/synthesize {text, language, voice?}   -> 200 audio/wav
                                                  header: X-TTS-Model-Version: <version>
```

Errors: `400` (malformed JSON body), `422` (empty text or unknown
language/voice), `502` (engine failed to produce audio).

`voice` is optional — omitted, the default voice for the language (first listed
in `config/services.yaml` under `tts.voices`) is used. `modelVersion` is opaque
but participates in Core's cache key: bump it whenever a voice model changes so
previously generated assets are superseded.

## Layout

```text
public/index.php           front controller (Symfony runtime)
src/Kernel.php             MicroKernel
src/Controller/            Health / Voices / Synthesize (attribute routes)
src/Voice/                 VoiceCatalog + Voice value object
src/Synthesis/             Synthesizer interface, Piper + Simulate engines, factory
config/services.yaml       DI wiring + tts.voices catalog
config/packages/           framework config
```

## Engine

Selected by `TTS_MODE`:

- `simulate` (default) — returns a valid **silent** WAV with no external binary
  or model. Used in CI and on hosts without an audio stack.
- `piper` — real synthesis via the [Piper](https://github.com/rhasspy/piper)
  native binary (offline, no Python). Set `PIPER_BINARY` and `PIPER_MODELS_DIR`,
  and make sure the model files referenced in `tts.voices` exist.

Swapping the engine (Piper → Silero/RHVoice/cloud) does not change the HTTP
contract — implement `AeroFlow\Tts\Synthesis\Synthesizer` and wire it in
`SynthesizerFactory`.

## Run

```bash
composer install
php -S 0.0.0.0:8080 -t public          # simulate mode
# or in Docker:
docker build -t aeroflow-tts . && docker run -p 8080:8080 aeroflow-tts
```

```bash
curl -s localhost:8080/v1/voices
curl -s -X POST localhost:8080/v1/synthesize \
  -H 'content-type: application/json' \
  -d '{"text":"Рейс 214 приглашается на посадку","language":"ru"}' --output out.wav
```

## Test

```bash
composer install
bin/phpunit
```

## Configuration

See `.env`. Voices are declared in `config/services.yaml` under the `tts.voices`
parameter. There is no database and no persisted state.
