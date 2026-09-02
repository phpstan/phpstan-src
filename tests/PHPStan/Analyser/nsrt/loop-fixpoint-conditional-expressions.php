<?php declare(strict_types = 1);

namespace LoopFixpointConditionalExpressions;

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
				assertType('list<LoopFixpointConditionalExpressions\Item>', $items);
				$item = array_shift($items);
				assertType('LoopFixpointConditionalExpressions\Item|null', $item);
				$toCurrent--;
			}
		}
	}

}
