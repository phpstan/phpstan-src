<?php declare(strict_types = 1);

namespace BugRule14807;

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
		echo 'go';
	}

	if ($color !== Color::Red && $item->ready === false) {
		throw new \RuntimeException('stop');
	}
}
