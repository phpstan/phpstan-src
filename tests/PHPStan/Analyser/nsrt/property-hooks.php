<?php // lint >= 8.4

declare(strict_types=1);

namespace PropertyHooksTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	public int $i {
		set {
			assertType('int', $value);
		}
	}

	public int $j {
		set (int $val) {
			assertType('int', $val);
		}
	}

	public int $k {
		set (int|string $val) {
			assertType('int|string', $val);
		}
	}

	/** @var array<string> */
	public array $l {
		set {
			assertType('array<string>', $value);
		}
	}

	/** @var array<string> */
	public array $m {
		set (array $val) {
			assertType('array<string>', $val);
		}
	}

	public int $n {
		/** @param int|array<string> $val */
		set (int|array $val) {
			assertType('array<string>|int', $val);
		}
	}

}

class FooShort
{

	public int $i {
		set => assertType('int', $value);
	}

	public int $j {
		set (int $val) => assertType('int', $val);
	}

	public int $k {
		set (int|string $val) => assertType('int|string', $val);
	}

	/** @var array<string> */
	public array $l {
		set => assertType('array<string>', $value);
	}

	/** @var array<string> */
	public array $m {
		set (array $val) => assertType('array<string>', $val);
	}

	public int $n {
		/** @param int|array<string> $val */
		set (int|array $val) => assertType('array<string>|int', $val);
	}

}

class FooConstructor
{

	public function __construct(
		public int $i {
			set {
				assertType('int', $value);
			}
		},
		public int $j {
			set (int $val) {
				assertType('int', $val);
			}
		},
		public int $k {
			set (int|string $val) {
				assertType('int|string', $val);
			}
		},
		/** @var array<string> */
		public array $l {
			set {
				assertType('array<string>', $value);
			}
		},
		/** @var array<string> */
		public array $m {
			set (array $val) {
				assertType('array<string>', $val);
			}
		},
		public int $n {
			/** @param int|array<string> $val */
			set (int|array $val) {
				assertType('array<string>|int', $val);
			}
		},
	) {

	}

}

class FooConstructorWithParam
{

	/**
	 * @param array<string> $l
	 * @param array<string> $m
	 */
	public function __construct(
		public array $l {
			set {
				assertType('array<string>', $value);
			}
		},
		public array $m {
			set (array $val) {
				assertType('array<string>', $val);
			}
		},
	) {

	}

}
