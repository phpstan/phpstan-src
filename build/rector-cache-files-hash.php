<?php declare(strict_types = 1);

use PHPStan\Build\RectorCache;

require_once __DIR__ . '/../vendor/autoload.php';

$cache = new RectorCache();
echo $cache->getOriginalFilesHash();
