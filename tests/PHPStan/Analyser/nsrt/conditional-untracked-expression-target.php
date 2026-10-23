<?php

namespace ConditionalUntrackedExpressionTarget;

use function PHPStan\Testing\assertType;

final class Item
{

	public ?int $key = null;

	public static ?string $name = null;

	public function getKey(): ?int
	{
		return $this->key;
	}

}

function g(int $i): void
{
}

function propertyFetch(Item $item): void
{
	if (rand(0, 1)) {
		$a = 1;
	} else {
		$a = 2;
		if ($item->key === null) {
			return;
		}
	}

	if ($item->key !== null) {
		if ($a === 1) {
			assertType('int', $item->key);
		}
		if ($a === 2) {
			assertType('int', $item->key);
		}
	}
	if ($a === 2) {
		assertType('int', $item->key);
	}
	if ($a === 1) {
		assertType('int|null', $item->key);
	}
}

function narrowedByTheSameCondition(Item $item, bool $resolve): void
{
	if ($item->key !== null && $resolve) {
		g($item->key);
	}
	if ($item->key !== null && !$resolve) {
		assertType('int', $item->key);
	}
}

function otherExpressions(Item $item, array $arr): void
{
	if (rand(0, 1)) {
		$a = 1;
	} else {
		$a = 2;
		if ($item->getKey() === null || Item::$name === null || !isset($arr['k'])) {
			return;
		}
	}

	if ($item->getKey() !== null && $a === 1) {
		assertType('int', $item->getKey());
	}
	if (Item::$name !== null && $a === 1) {
		assertType('string', Item::$name);
	}
	if (isset($arr['k']) && $a === 1) {
		assertType('mixed~null', $arr['k']);
	}
}

function variableStaysUndefined(?int $key): void
{
	if (rand(0, 1)) {
		$a = 1;
	} else {
		$a = 2;
		$x = $key;
	}

	if ($a === 1) {
		assertType('*ERROR*', $x);
	}
}
