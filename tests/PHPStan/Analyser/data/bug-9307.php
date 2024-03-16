<?php declare(strict_types=1);

namespace Bug9307;

use function PHPStan\Testing\assertType;

class Item {
	public int $id;
}

class Aaa
{
	public function test(): void
	{
		$objects = [];
		$itemCache = [];

		foreach ($this->getIds() as $id) {
			if (! array_key_exists($id, $itemCache)) {
				$items = $this->getObjects();
				$itemCache[$id] = $items;
			} else {
				$items = $itemCache[$id];
			}

			// It works when the following line is uncommented.
			//$items = $this->getObjects();

			foreach ($items as $item) {
				$objects[$item->id] = $item;
			}
		}

		assertType('array', $objects);

		$this->acceptObjects($objects);
	}

	/** @return array<int> */
	public function getIds(): array
	{
		return [];
	}

	/** @return array<int, Item> */
	public function getObjects(): array
	{
		return [];
	}

	/** @param array<int, Item> $objects */
	public function acceptObjects(array $objects): void
	{

	}
}
