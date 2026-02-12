<?php

declare(strict_types=1);

namespace Atk4\Data\Bootstrap;

use Doctrine\DBAL\Platforms\SqlitePlatform;

// force SQLitePlatform class load as in DBAL 3.x it is named with a different case
// remove once DBAL 3.x support is dropped
try {
    new SqlitePlatform(); // @phpstan-ignore class.notFound
} catch (\Error $e) {
}
