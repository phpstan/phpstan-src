<?php declare(strict_types = 1);

namespace LoopFixpointConditionalExpressions;

use LogicException;
use function PHPStan\Testing\assertType;

final class Item
{

	public function assign(): void
	{
	}

}

abstract class Foo
{

	/** @return list<Item> */
	abstract public function getItems(): array;

	public function run(int $quantityToAllocate, int $unitsThatFit): void
	{
		$items = $this->getItems();
		if ($quantityToAllocate <= 0) {
			return;
		}
		if ($unitsThatFit <= 0) {
			return;
		}

		do {
			$unitsAdded = min($unitsThatFit, $quantityToAllocate);
			$toCurrent = 0;
			if ($items !== []) {
				$unitsAdded = min($unitsAdded, count($items));
				$toCurrent = $unitsAdded;
			}

			while ($toCurrent > 0) {
				assertType('list<LoopFixpointConditionalExpressions\Item>', $items);
				$item = array_shift($items);
				assertType('LoopFixpointConditionalExpressions\Item|null', $item);
				if ($item === null) {
					throw new LogicException();
				}
				$item->assign();
				$toCurrent--;
			}

			$quantityToAllocate -= $unitsAdded;
		} while ($quantityToAllocate > 0);
	}

}
