# Minimal stateless TTS service (Symfony framework-bundle). Defaults to simulate
# mode (no audio stack).
#
# For real synthesis, set TTS_MODE=piper and provide the Piper binary and voice
# models — either bake them into a derived image (COPY the binary onto PATH and
# the *.onnx / *.onnx.json models into PIPER_MODELS_DIR) or mount them at runtime.
FROM php:8.4.27-cli-alpine

RUN apk add --no-cache git unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock* ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --optimize-autoloader

COPY . .

ENV APP_ENV=prod
ENV TTS_MODE=simulate
EXPOSE 8080

# Built-in PHP server is adequate for the low-volume, on-prem pre-generation use.
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
