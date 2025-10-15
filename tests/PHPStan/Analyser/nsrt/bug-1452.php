<?php declare(strict_types = 1);

namespace Bug1452;

use function PHPStan\Testing\assertType;

$dateInterval = (new \DateTimeImmutable('now -60 minutes'))->diff(new \DateTimeImmutable('now'));

assertType(
    'lowercase-string&non-empty-string',
    $dateInterval->format('%a')
);
