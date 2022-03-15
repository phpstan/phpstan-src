<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug6494;

use function PHPStan\Testing\assertType;

// To get rid of warnings about using new static()
interface SomeInterface {
	public function __construct();
}

class Base implements SomeInterface {

	public function __construct() {}

	/**
	 * @return \Generator<int, static, void, void>
	 */
	public static function instances() {
		yield new static();
	}
}

function (): void {
	foreach ((new Base())::instances() as $h) {
		assertType(Base::class, $h);
	}
};

class Extension extends Base {

}

function (): void {
	foreach ((new Extension())::instances() as $h) {
		assertType(Extension::class, $h);
	}
};
