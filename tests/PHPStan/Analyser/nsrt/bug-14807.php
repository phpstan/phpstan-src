<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14807;

use function PHPStan\Testing\assertType;

enum Color
{
	case Red;
	case Blue;
}

class Item
{
	public bool $ready = false;
}

function process(Color $color, Item $item): void
{
	if ($color !== Color::Red && $item->ready === true) {
		assertType('true', $item->ready);
	}

	// The narrowing from the first `if` must not leak here: reusing the same
	// enum left-hand condition does not imply $item->ready is still true.
	if ($color !== Color::Red) {
		assertType('bool', $item->ready);
	}

	if ($color !== Color::Red && $item->ready === false) {
		assertType('false', $item->ready);
	}
}
