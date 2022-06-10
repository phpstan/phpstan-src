<?php declare(strict_types = 1);

namespace Discussion7450;

use function PHPStan\Testing\assertType;

/** @param array{policy: non-empty-string, entitlements: non-empty-string[]} $foo */
function foo(array $foo): void
{
}

$args = [
	'policy' => $_POST['policy'], // shouldn't this and the next line be an unsafe offset access?
	'entitlements' => $_POST['entitlements'],
];
assertType('mixed', $_POST['policy']);
assertType('array{policy: mixed, entitlements: mixed}', $args);
foo($args); // I'd expect this to be reported too

/** @var mixed $mixed */
$mixed = null;
$args = [
	'policy' => $mixed,
	'entitlements' => $mixed,
];
assertType('mixed', $mixed);
assertType('array{policy: mixed, entitlements: mixed}', $args);
foo($args);
