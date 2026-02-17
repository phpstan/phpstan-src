<?php // lint >= 8.1

namespace Bug11145 {
	class Bar {}
}

namespace {

	define('BUG_11145_FOO', 'foo');

	use Bug11145\Bar;

	class Bug11145Baz {
		public function __construct(readonly public Bar $bar) {}
	}

	class Bug11145Container {
		public function get(string $id): object {
			return new \stdClass();
		}
	}

	/** @var Bar $bar */
	$bar = (new Bug11145Container())->get('anything');
	\PHPStan\Testing\assertType('Bug11145\Bar', $bar);

	$baz = new Bug11145Baz($bar);
	\PHPStan\Testing\assertType('Bug11145Baz', $baz);
}
