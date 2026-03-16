<?php

/**
 * Manual XP curve.
 *
 * Rationale:
 * - Explicit thresholds are easier to reason about and safer to rebalance.
 * - We avoid short early curves that force formula extrapolation too soon.
 * - The mid/late game opens up more gradually, so 4k–8k XP does not look absurdly high.
 *
 * Each value is the minimum lifetime XP required to reach that level.
 */
return [
    1  => 0,
    2  => 100,
    3  => 250,
    4  => 450,
    5  => 700,
    6  => 1000,
    7  => 1400,
    8  => 1900,
    9  => 2500,
    10 => 3250,
    11 => 4150,
    12 => 5200,
    13 => 6400,
    14 => 7750,
    15 => 9250,
    16 => 10900,
    17 => 12700,
    18 => 14650,
    19 => 16750,
    20 => 19000,
    21 => 21400,
    22 => 23950,
    23 => 26650,
    24 => 29500,
    25 => 32500,
    26 => 35650,
    27 => 38950,
    28 => 42400,
    29 => 46000,
    30 => 49750,
];
