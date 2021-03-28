<?php

namespace Bug1597;

use function PHPStan\Analyser\assertType;

$date = '';

try {
	$date = new \DateTime($date);
} catch (\Exception $e) {
	assertType('\'\'', $date);
}
