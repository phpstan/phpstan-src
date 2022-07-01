<?php // lint >= 8.1

namespace Bug7211;

enum Foo {
	case Bar;
	case Baz;
	case Gar;
	case Gaz;

	public function startsWithB(): bool
	{
		return inArray($this, [static::Baz, static::Bar]);
	}
}

/**
 * @template    T
 * @psalm-param T $needle
 * @psalm-param array<array-key, T> $haystack
 */
function inArray(mixed $needle, array $haystack): bool
{
	return \in_array($needle, $haystack, true);
}
