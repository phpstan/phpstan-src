<?php declare(strict_types = 1);

namespace ArrayShiftWhileCountLoop;

use function PHPStan\Testing\assertType;

final class Item
{
}

class Foo
{

	/** @param list<Item> $items */
	public function run(array $items): void
	{
		foreach ([1, 2] as $_) {
			$toCurrent = 0;
			if ($items !== []) {
				$toCurrent = count($items);
			}

			while ($toCurrent > 0) {
				// array_shift() empties $items across iterations: the loop must not
				// keep the guard's "$toCurrent positive means $items non-empty"
				// conditional, so the shifted item is nullable
				assertType('list<ArrayShiftWhileCountLoop\Item>', $items);
				$item = array_shift($items);
				assertType('ArrayShiftWhileCountLoop\Item|null', $item);
				$toCurrent--;
			}
		}
	}

}
