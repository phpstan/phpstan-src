<?php declare(strict_types = 1);

namespace SideEffectsWrittenArguments;

use function PHPStan\Testing\assertType;

class Foo
{

	public ?int $bar = null;

}

function reset(Foo $foo): void
{
}

/**
 * @pure-unless-parameter-passed $foo
 */
function maybeReset(int $i, ?Foo $foo = null): int
{
	return $i;
}

function (Foo $foo): void {
	if ($foo->bar === null) {
		return;
	}

	// prints, but does not write its arguments
	var_dump($foo);
	assertType('int', $foo->bar);

	// can do anything, including writing its arguments
	reset($foo);
	assertType('int|null', $foo->bar);
};

function (Foo $foo): void {
	if ($foo->bar === null) {
		return;
	}

	maybeReset(1);
	assertType('int', $foo->bar);

	// writes the argument passed to $foo
	maybeReset(1, $foo);
	assertType('int|null', $foo->bar);
};
