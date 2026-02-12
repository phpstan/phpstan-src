<?php

declare(strict_types=1);

namespace Atk4\Data\Persistence\Sql;

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

class DbalDriverMiddleware extends AbstractDriverMiddleware
{
    protected function replaceDatabasePlatform(AbstractPlatform $platform, ?string $version): AbstractPlatform
    {
        if ($platform instanceof SQLitePlatform) {
            $platform = new class extends SQLitePlatform {};
        }

        return $platform;
    }
}
