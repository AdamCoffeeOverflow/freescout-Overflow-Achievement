<?php

namespace Modules\OverflowAchievement\Services;

use Modules\OverflowAchievement\Entities\Achievement;

class QuoteService
{
    /**
     * Return a quote for a specific achievement.
     *
     * Preference order:
     * 1) achievement.quote_text (custom)
     * 2) achievement.quote_id lookup in quote library
     * 3) deterministic selection from quote library by achievement key
     */
    public function forAchievement(Achievement $achievement): array
    {
        $custom = trim((string)($achievement->quote_text ?? ''));
        if ($custom !== '') {
            return [
                'id' => $achievement->quote_id ?? null,
                'text' => $this->trimToMax($custom),
                'author' => $achievement->quote_author ?? null,
            ];
        }

        $quote_id = trim((string)($achievement->quote_id ?? ''));
        if ($quote_id !== '') {
            $q = $this->getById($quote_id);
            if (!empty($q['text'])) {
                return [
                    'id' => $q['id'] ?? $quote_id,
                    'text' => $this->trimToMax((string)$q['text']),
                    'author' => $q['author'] ?? null,
                ];
            }
        }

        return $this->deterministicByKey((string)$achievement->key, $achievement->mailbox_id ?? null);
    }

    /**
     * Lookup a quote in the library by ID.
     */
    public function getById(string $id): array
    {
        $library = (array)config('overflowachievement.quotes.library', []);
        foreach ($library as $q) {
            if (!empty($q['id']) && $q['id'] === $id) {
                return $q;
            }
        }
        return ['id' => $id, 'text' => null, 'author' => null];
    }

    /**
     * Pick the next available quote ID based on rarity + optional tone.
     *
     * This is used when creating/editing achievements where we want quotes to be:
     * - stable (a trophy keeps its quote)
     * - unique when possible (avoid IDs already assigned)
     * - vibe-matched (legendary feels more epic/philosophical)
     */
    public function pickIdForRarityTone(string $rarity, string $tone = '', array $avoid_ids = [], $mailbox_id = null): ?string
    {
        $rarity = in_array($rarity, ['common','rare','epic','legendary']) ? $rarity : 'common';
        $tone = trim((string)$tone);

        // Mailbox-aware tone hints (optional). This does not change the quote library;
        // it only changes which quote buckets we try first when auto-assigning.
        // Config shape:
        // overflowachievement.quotes.mailbox_preferences = [
        //   1 => ['tones' => ['funny','epic']],
        //   2 => ['tones' => ['epic','philosophical']],
        // ]
        $mailbox_tones = [];
        if (!empty($mailbox_id)) {
            $mb = (array)config('overflowachievement.quotes.mailbox_preferences.'.(string)$mailbox_id, []);
            $mailbox_tones = array_values(array_filter((array)($mb['tones'] ?? [])));
        }

        $buckets = (array)config('overflowachievement.quotes.buckets', []);
        $prefs = (array)config('overflowachievement.quotes.rarity_preferences', []);

        // Optional mailbox-specific quote library subset.
        // If configured, we restrict auto-selection to that subset first.
        $mailbox_lib_ids = $this->mailboxLibraryIds($mailbox_id);
        $mailbox_lib_map = !empty($mailbox_lib_ids) ? array_fill_keys($mailbox_lib_ids, true) : [];

        $tryBuckets = [];

        // Mailbox-first preferences.
        foreach ($mailbox_tones as $t) {
            if (!in_array($t, $tryBuckets, true)) {
                $tryBuckets[] = $t;
            }
        }

        if ($tone !== '' && !empty($buckets[$tone])) {
            $tryBuckets[] = $tone;
        }
        foreach (($prefs[$rarity] ?? []) as $t) {
            if (!in_array($t, $tryBuckets, true)) {
                $tryBuckets[] = $t;
            }
        }

        // Fallback: any bucket, then full library.
        if (empty($tryBuckets)) {
            $tryBuckets = array_keys($buckets);
        }

        $avoidMap = array_fill_keys(array_filter($avoid_ids), true);

        // Build lookup from ID => quote for quick checks.
        $library = (array)config('overflowachievement.quotes.library', []);
        $libIds = [];
        foreach ($library as $q) {
            if (!empty($q['id'])) {
                $libIds[] = $q['id'];
            }
        }

        // First pass: try buckets in priority order.
        foreach ($tryBuckets as $bucketName) {
            $ids = (array)($buckets[$bucketName] ?? []);
            if (!empty($mailbox_lib_map)) {
                // Restrict to mailbox subset.
                $ids = array_values(array_filter($ids, function ($id) use ($mailbox_lib_map) {
                    return !empty($mailbox_lib_map[$id]);
                }));
            }
            foreach ($ids as $id) {
                if (empty($avoidMap[$id])) {
                    return $id;
                }
            }
        }

        // Second pass (mailbox subset): if buckets are empty but a mailbox library exists,
        // pick any unused ID from that subset.
        if (!empty($mailbox_lib_ids)) {
            foreach ($mailbox_lib_ids as $id) {
                if (empty($avoidMap[$id])) {
                    return $id;
                }
            }
        }

        // Final pass: full library.
        foreach ($libIds as $id) {
            if (empty($avoidMap[$id])) {
                return $id;
            }
        }

        // Library exhausted.
        return !empty($libIds) ? $libIds[0] : null;
    }

    /**
     * Return the full quote library for admin UIs.
     */
    public function all(): array
    {
        return array_values((array)config('overflowachievement.quotes.library', []));
    }

    /**
     * Deterministically pick a quote from the library using the achievement key.
     */
    public function deterministicByKey(string $key, $mailbox_id = null): array
    {
        $library = array_values((array)config('overflowachievement.quotes.library', []));
        if (empty($library)) {
            // Fallback to themed pools if library not present.
            return $this->pick('generic', []);
        }

        // If a mailbox-specific subset is configured, deterministically pick within that subset.
        $mailbox_lib_ids = $this->mailboxLibraryIds($mailbox_id);
        if (!empty($mailbox_lib_ids)) {
            $subset = [];
            $subsetMap = array_fill_keys($mailbox_lib_ids, true);
            foreach ($library as $q) {
                if (!empty($q['id']) && !empty($subsetMap[$q['id']])) {
                    $subset[] = $q;
                }
            }
            if (!empty($subset)) {
                $library = $subset;
            }
        }

        // Stable hash -> index.
        $salt = 'oa:'.$key;
        if (!empty($mailbox_id)) {
            $salt .= ':mb:'.(string)$mailbox_id;
        }
        $hash = crc32($salt);
        $idx = (int)($hash % count($library));
        $q = $library[$idx] ?? [];

        return [
            'id' => $q['id'] ?? null,
            'text' => $this->trimToMax((string)($q['text'] ?? '')),
            'author' => $q['author'] ?? null,
        ];
    }

    /**
     * Pick a quote for a theme, avoiding recently used quote IDs.
     */
    public function pick(string $theme, array $avoid_ids = []): array
    {
        $quotes = config('overflowachievement.quotes');

        $pool = $quotes[$theme] ?? $quotes['generic'] ?? [];
        if (empty($pool)) {
            return ['id' => null, 'text' => null, 'author' => null];
        }

        $filtered = array_values(array_filter($pool, function ($q) use ($avoid_ids) {
            return empty($q['id']) || !in_array($q['id'], $avoid_ids);
        }));

        $pick_from = !empty($filtered) ? $filtered : $pool;
        $q = $pick_from[array_rand($pick_from)];

        $text = $this->trimToMax((string)($q['text'] ?? ''));

        return [
            'id' => $q['id'] ?? null,
            'text' => $text ?: null,
            'author' => $q['author'] ?? null,
        ];
    }

    protected function trimToMax(string $text): string
    {
        $max_len = (int)config('overflowachievement.quotes.max_length', 140);
        $text = (string)$text;
        if ($max_len > 0 && $text !== '' && mb_strlen($text) > $max_len) {
            return rtrim(mb_substr($text, 0, $max_len - 1)).'…';
        }
        return $text;
    }

    /**
     * Return mailbox-specific quote IDs subset (if configured).
     */
    protected function mailboxLibraryIds($mailbox_id): array
    {
        if (empty($mailbox_id)) {
            return [];
        }

        // 1) Admin-configured mailbox rules (stored in Option as JSON).
        $rules_raw = (string)\Option::get('overflowachievement.quotes.mailbox_rules', '');
        $rules = [];
        if ($rules_raw !== '') {
            $decoded = json_decode($rules_raw, true);
            if (is_array($decoded)) {
                $rules = $decoded;
            }
        }

        $mb_rule = $rules[(string)$mailbox_id] ?? null;
        if (is_array($mb_rule)) {
            // If explicit IDs are provided, use them directly.
            $explicit = array_values(array_filter(array_map(function ($v) {
                return trim((string)$v);
            }, (array)($mb_rule['ids'] ?? []))));
            if (!empty($explicit)) {
                return $explicit;
            }

            // Otherwise generate a subset from selected tones.
            $tones = array_values(array_filter(array_map('strval', (array)($mb_rule['tones'] ?? []))));
            $limit = (int)($mb_rule['limit'] ?? 0);

            if (!empty($tones)) {
                $buckets = (array)config('overflowachievement.quotes.buckets', []);
                $ids = [];
                foreach ($tones as $t) {
                    foreach ((array)($buckets[$t] ?? []) as $id) {
                        $id = trim((string)$id);
                        if ($id !== '') {
                            $ids[$id] = true;
                        }
                    }
                }
                $ids = array_keys($ids);

                // Deterministic shuffle so it's stable across requests.
                sort($ids);
                usort($ids, function ($a, $b) use ($mailbox_id) {
                    $ha = crc32('oa:mb:'.(string)$mailbox_id.':'.$a);
                    $hb = crc32('oa:mb:'.(string)$mailbox_id.':'.$b);
                    if ($ha === $hb) {
                        return strcmp($a, $b);
                    }
                    return ($ha < $hb) ? -1 : 1;
                });

                if ($limit > 0) {
                    $ids = array_slice($ids, 0, $limit);
                }

                if (!empty($ids)) {
                    return $ids;
                }
            }
        }

        // 2) Config-defined mailbox libraries (static subsets).
        $ids = (array)config('overflowachievement.quotes.mailbox_libraries.'.(string)$mailbox_id, []);
        $ids = array_values(array_filter(array_map(function ($v) {
            return trim((string)$v);
        }, $ids)));

        return $ids;
    }
}
