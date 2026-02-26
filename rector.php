<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__,
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/public/vendor',
    ]);
};
