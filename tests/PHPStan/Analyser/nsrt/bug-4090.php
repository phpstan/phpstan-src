<?php declare(strict_types = 1);

namespace Bug4090;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param string[] $items
	 */
	public function test(string $value, array $items): void
	{
		if (in_array($value, $items, true)) {
			assertType('non-empty-array<string>', $items);
			$first = current($items);
			assertType('string', $first);
		}
	}

}
