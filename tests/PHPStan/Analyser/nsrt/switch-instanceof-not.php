<?php

namespace SwitchInstanceOfNot;

use function PHPStan\Testing\assertType;

$foo = doFoo();

switch (false) {
	case $foo instanceof Foo:
		assertType('*NEVER*', $foo);
}
