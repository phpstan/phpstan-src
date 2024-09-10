<?php // lint >= 8.1

namespace Bug10685;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template A
	 * @param A $value
	 * @return A
	 */
	function identity(mixed $value): mixed
	{
		return $value;
	}

	public function doFoo(): void
	{
		assertType('array{1|2|3, 1|2|3, 1|2|3}', array_map(fn($i) => $i, [1, 2, 3]));
		assertType('array{1, 2, 3}', array_map($this->identity(...), [1, 2, 3]));
	}

}
