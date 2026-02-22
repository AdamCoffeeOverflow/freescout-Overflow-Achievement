<?php

namespace Modules\OverflowAchievement\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class LevelService
{
    /**
     * Dynamic scaling so progression stays sane as more trophies/triggers are enabled.
     *
     * Philosophy: more active achievements usually means more XP inflow.
     * We gently scale the curve upward so leveling still feels earned.
     */
    protected function scaleFactor(): float
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        // Default: on, conservative.
        $enabled = (bool)config('overflowachievement.levels_dynamic.enabled', true);
        if (!$enabled) {
            return $cached = 1.0;
        }

        try {
            if (!Schema::hasTable('overflowachievement_achievements')) {
                return $cached = 1.0;
            }

            $active = (int)DB::table('overflowachievement_achievements')->where('is_active', true)->count();
            $baseline = (int)config('overflowachievement.levels_dynamic.baseline_achievements', 60);
            $step = (float)config('overflowachievement.levels_dynamic.step', 0.005); // +0.5% per extra trophy
            $min = (float)config('overflowachievement.levels_dynamic.min', 0.90);
            $max = (float)config('overflowachievement.levels_dynamic.max', 1.60);

            $delta = $active - $baseline;
            $factor = 1.0 + ($delta * $step);
            $factor = max($min, min($max, $factor));

            return $cached = $factor;
        } catch (\Throwable $e) {
            return $cached = 1.0;
        }
    }

    /**
     * Get configured level curve.
     *
     * Supports a config array:
     *  [1 => 0, 2 => 100, 3 => 250, ...]
     */
    protected function curve(): array
    {
        // Cache the curve within the request. This method may be called many times
        // per page load (navbar render, unseen polling, toast sequencing).
        static $cache = [];

        $raw = (array)config('overflowachievement.levels', []);
        $factor = $this->scaleFactor();
        $cacheKey = md5(json_encode($raw)."|".sprintf('%.6f', $factor));
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $curve = $raw;

        // Normalize (positive integer keys, integer values)
        $norm = [];
        foreach ($curve as $k => $v) {
            $lk = (int)$k;
            if ($lk < 1) {
                continue;
            }
            $norm[$lk] = max(0, (int)$v);
        }

        if (empty($norm)) {
            // Safe fallback that matches shipped defaults.
            $norm = [1 => 0, 2 => 100, 3 => 250, 4 => 450, 5 => 700, 6 => 1000, 7 => 1350, 8 => 1750, 9 => 2200, 10 => 2700];
        }

        if (!isset($norm[1])) {
            $norm[1] = 0;
        }

        ksort($norm);

        if (abs($factor - 1.0) < 0.0001) {
            return $cache[$cacheKey] = $norm;
        }

        // Scale each threshold. Level 1 stays 0.
        $scaled = [];
        foreach ($norm as $lvl => $minXp) {
            if ((int)$lvl === 1) {
                $scaled[$lvl] = 0;
                continue;
            }
            $scaled[$lvl] = (int)round($minXp * $factor);
        }
        return $cache[$cacheKey] = $scaled;
    }

    public function levelMinXp(int $level): int
    {
        $level = max(1, (int)$level);
        $curve = $this->curve();

        if (isset($curve[$level])) {
            return (int)$curve[$level];
        }

        // Extrapolate beyond configured curve using the last observed delta.
        $levels = array_keys($curve);
        $lastLevel = (int)end($levels);
        $lastMin = (int)$curve[$lastLevel];
        $prevLevel = $lastLevel > 1 ? $lastLevel - 1 : 1;
        $prevMin = isset($curve[$prevLevel]) ? (int)$curve[$prevLevel] : max(0, $lastMin - 250);
        $delta = max(100, $lastMin - $prevMin);

        // Increase delta slightly each level.
        $growth = 1.15;
        $min = $lastMin;
        for ($l = $lastLevel + 1; $l <= $level; $l++) {
            $min += (int)round($delta);
            $delta = (int)round($delta * $growth);
        }
        return (int)$min;
    }

    public function nextLevelMinXp(int $current_level): int
    {
        return $this->levelMinXp(max(1, $current_level) + 1);
    }

    public function levelForXp(int $xp_total): int
    {
        $xp_total = max(0, (int)$xp_total);
        $curve = $this->curve();

        $level = 1;
        foreach ($curve as $l => $min) {
            if ($xp_total >= (int)$min) {
                $level = (int)$l;
            } else {
                break;
            }
        }

        // If XP exceeds configured max, extrapolate.
        $levels = array_keys($curve);
        $maxConfigured = (int)end($levels);
        if ($level === $maxConfigured) {
            while (true) {
                $nextMin = $this->levelMinXp($level + 1);
                if ($xp_total < $nextMin) {
                    break;
                }
                $level++;
                if ($level > 10000) {
                    break;
                }
            }
        }

        return max(1, (int)$level);
    }
}
