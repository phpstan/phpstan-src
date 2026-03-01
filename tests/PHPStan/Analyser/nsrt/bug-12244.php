<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug12244;

use function PHPStan\Testing\assertType;

enum X: string {
	case A = 'a';
	case B = 'b';
	case C = 'c';

	/** @return ($this is self::A ? int : null) */
	public function get(): ?int {
		return ($this === self::A) ? 123 : null;
	}

	public function doSomething(): void {
		if ($this !== self::A) {
			assertType('$this(Bug12244\X~Bug12244\X::A)', $this);
			assertType('null', $this->get());
		} else {
			assertType('int', $this->get());
		}

		if ($this !== self::B && $this !== self::C) {
			assertType('int', $this->get());
		}
	}

	public static function doSomethingFor(X $x): void {
		if ($x !== self::A) {
			assertType('Bug12244\X~Bug12244\X::A', $x);
			assertType('null', $x->get());
		} else {
			assertType('int', $x->get());
		}

		if ($x !== self::B && $x !== self::C) {
			assertType('int', $x->get());
		}
	}
}
