<?php declare(strict_types = 1);

namespace Bug13959;

use InvalidArgumentException;
use function PHPStan\Testing\assertType;

interface GlobalId {}
class GlobalTagId implements GlobalId {}

class HelloWorld
{
	/**
	 * @param list<GlobalId|string> $value
	 */
	public function sayHello(array $value): void
	{
		assertType('list<Bug13959\GlobalId|string>', $value);

		foreach ($value as $item) {
			if ($item instanceof GlobalTagId) {
				continue;
			}

			if (is_string($item)) {
				continue;
			}

			throw new InvalidArgumentException('Invalid type');
		}
		
		assertType('list<Bug13959\GlobalTagId|string>', $value);
	}
}
