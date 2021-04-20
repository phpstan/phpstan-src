<?php

namespace Bug1597;

use function PHPStan\Testing\assertType;

$date = '';

try {
	$date = new \DateTime($date);
} catch (\Exception $e) {
	assertType('\'\'', $date);
}
