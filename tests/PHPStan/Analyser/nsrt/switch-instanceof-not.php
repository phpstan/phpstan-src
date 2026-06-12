<?php

namespace SwitchInstanceOfNot;

use function PHPStan\Testing\assertType;

class Foo {}
class Bar {}

/** @var Foo|Bar $foo */
$foo = doFoo();

switch (false) {
	case $foo instanceof Foo:
		assertType('SwitchInstanceOfNot\Bar', $foo);
}
