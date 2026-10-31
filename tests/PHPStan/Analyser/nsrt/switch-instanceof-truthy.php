<?php

namespace SwitchInstanceOf;

use function PHPStan\Testing\assertType;

/** @var \stdClass $object */
$object = doFoo();

$foo = doFoo();
$bar = doBar();
$baz = doBaz();

switch ($object) {
	case $foo instanceof Foo:
		break;
	case $bar instanceof Bar:
		break;
	case $baz instanceof Baz:
		assertType('*ERROR*', $foo);
		assertType('*ERROR*', $bar);
		assertType('SwitchInstanceOf\Baz', $baz);
		break;
}
