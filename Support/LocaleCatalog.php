<?php

namespace Modules\OverflowAchievement\Support;

class LocaleCatalog
{
    public static function supported(): array
    {
        return ['en', 'fr', 'es', 'de', 'it', 'nl', 'pl', 'pt_BR'];
    }

    public static function aliases(): array
    {
        return [
            'en_US' => 'en',
            'en-GB' => 'en',
            'fr_CA' => 'fr',
            'fr-CA' => 'fr',
            'es_MX' => 'es',
            'es-ES' => 'es',
            'de_AT' => 'de',
            'de-AT' => 'de',
            'de_CH' => 'de',
            'de-CH' => 'de',
            'it_IT' => 'it',
            'it-IT' => 'it',
            'nl_BE' => 'nl',
            'nl-BE' => 'nl',
            'pl_PL' => 'pl',
            'pl-PL' => 'pl',
            'pt' => 'pt_BR',
            'pt_BR' => 'pt_BR',
            'pt-BR' => 'pt_BR',
            'pt_PT' => 'pt_BR',
            'pt-PT' => 'pt_BR',
        ];
    }

    public static function normalize($locale = null): ?string
    {
        $locale = trim((string) $locale);
        if ($locale === '') {
            return null;
        }

        if (isset(static::aliases()[$locale])) {
            return static::aliases()[$locale];
        }

        $base = str_replace('-', '_', $locale);
        if (isset(static::aliases()[$base])) {
            return static::aliases()[$base];
        }

        if (in_array($base, static::supported(), true)) {
            return $base;
        }

        $language = strtolower((string) strtok($base, '_'));
        if (in_array($language, static::supported(), true)) {
            return $language;
        }

        return $base;
    }

    public static function translationCandidates($locale = null): array
    {
        $candidates = [];
        if ($locale === null || trim((string) $locale) === '') {
            $locale = app()->getLocale();
        }
        $normalized = static::normalize($locale);
        if ($normalized) {
            $candidates[] = $normalized;
        }

        $locale = trim((string) $locale);
        if ($locale !== '') {
            $base = str_replace('-', '_', $locale);
            if ($base !== $normalized) {
                $candidates[] = $base;
            }
            $language = strtolower((string) strtok($base, '_'));
            if ($language && $language !== $normalized) {
                $candidates[] = $language;
            }
        }

        $candidates[] = 'en';

        return array_values(array_unique(array_filter($candidates)));
    }
}
