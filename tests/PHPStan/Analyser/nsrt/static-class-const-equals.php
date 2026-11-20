<?php

namespace StaticClassConstEquals;

use function PHPStan\Testing\assertType;

interface A {
	public function equals(A $other): bool;
}

class B implements A {
	public function equals(A $other): bool {
		if ($other::class === static::class) {
			assertType('static(StaticClassConstEquals\\B)', $other);
			return true;
		} else {
			assertType('StaticClassConstEquals\\A', $other);
			return false;
		}
	}
}

class C implements A {
	public function equals(A $other): bool {
		if (get_class($other) === static::class) {
			assertType('static(StaticClassConstEquals\\C)', $other);
			return true;
		} else {
			assertType('StaticClassConstEquals\\A', $other);
			return false;
		}
	}
}

class D implements A {
	public function equals(A $other): bool {
		if ($other::class === self::class) {
			assertType('StaticClassConstEquals\\D', $other);
			return true;
		} else {
			assertType('StaticClassConstEquals\\A', $other);
			return false;
		}
	}
}

class E implements A {
	public function equals(A $other): bool {
		if (get_class($other) === self::class) {
			assertType('StaticClassConstEquals\\E', $other);
			return true;
		} else {
			assertType('StaticClassConstEquals\\A', $other);
			return false;
		}
	}
}
