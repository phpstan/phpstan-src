<?php declare(strict_types = 1);

namespace Bug14054Property;

/**
 * @property-read array<int, string> $items
 * @property-write array<int, string>|string $items
 */
final class Magic
{

	public function __get(string $name): mixed
	{
		return [];
	}

	public function __set(string $name, mixed $value): void
	{
	}

}

function testMagic(Magic $m): void
{
	$m->items[] = 'x';
	$m->items['key'] = 'y';
}
