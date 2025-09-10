<?php declare(strict_types = 1);

namespace Bug1452;

use function PHPStan\Testing\assertType;

$dateInterval = (new \DateTimeImmutable('now -60 minutes'))->diff(new \DateTimeImmutable('now'));

// Could be lowercase-string&non-falsy-string&numeric-string&uppercase-string
assertType('lowercase-string&non-falsy-string', $dateInterval->format('%a'));
