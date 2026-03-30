<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12241;

/**
 * @phpstan-sealed Bar|Baz
 */
abstract class Foo{}

final class Bar extends Foo{}
final class Baz extends Foo{}

function (Foo $foo): string {
	return match ($foo::class) {
		Bar::class => 'Bar',
		Baz::class => 'Baz',
	};
};
