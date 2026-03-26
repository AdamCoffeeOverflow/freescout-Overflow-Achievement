<?php

namespace Modules\OverflowAchievement\Support;

class QuoteCatalog
{
    public static function translationKey(string $quoteId, string $field): string
    {
        return 'overflowachievement::quotes.' . trim($quoteId) . '.' . trim($field);
    }

    public static function translated(string $quoteId, string $field, $locale = null): ?string
    {
        $quoteId = trim((string) $quoteId);
        $field = trim((string) $field);
        if ($quoteId === '' || $field === '') {
            return null;
        }

        $key = static::translationKey($quoteId, $field);
        foreach (LocaleCatalog::translationCandidates($locale) as $candidateLocale) {
            $text = app('translator')->get($key, [], $candidateLocale);
            if ((string) $text !== $key) {
                return (string) $text;
            }
        }

        return null;
    }

    public static function localizeQuote(array $quote, $locale = null): array
    {
        return [
            'id' => $quote['id'] ?? null,
            'text' => static::localizeText($quote['id'] ?? null, $quote['text'] ?? '', $locale),
            'author' => static::localizeAuthor($quote['id'] ?? null, $quote['author'] ?? '', $locale),
        ];
    }

    public static function localizeText($quoteId, $fallbackText = '', $locale = null): string
    {
        $translated = static::translated((string) $quoteId, 'text', $locale);
        if ($translated !== null) {
            return $translated;
        }

        return AchievementCatalog::localizeText($fallbackText);
    }

    public static function localizeAuthor($quoteId, $fallbackAuthor = '', $locale = null): string
    {
        $translated = static::translated((string) $quoteId, 'author', $locale);
        if ($translated !== null) {
            return $translated;
        }

        return AchievementCatalog::localizeText($fallbackAuthor);
    }

    public static function library(): array
    {
        return array_values((array) config('overflowachievement.quotes.library', []));
    }

    public static function matchIdByText(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        foreach (static::library() as $quote) {
            if (trim((string) ($quote['text'] ?? '')) === $text) {
                $id = trim((string) ($quote['id'] ?? ''));
                return $id !== '' ? $id : null;
            }
        }

        return null;
    }
}
