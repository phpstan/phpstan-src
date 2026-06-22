<?php declare(strict_types = 1);

namespace Bug14847;

use function PHPStan\Testing\assertType;

class Foo
{

	public ?string $n = null;

	public static ?string $s = null;

}

function narrowFromBareword(Foo $obj): void
{
	if ($obj->n !== null) {
		assertType('string', $obj->n);
		assertType('string', $obj->{'n'});
	}
}

function narrowFromCurly(Foo $obj): void
{
	if ($obj->{'n'} !== null) {
		assertType('string', $obj->{'n'});
		assertType('string', $obj->n);
	}
}

function narrowStaticProperty(): void
{
	if (Foo::$s !== null) {
		assertType('string', Foo::$s);
		assertType('string', Foo::${'s'});
	}

	if (Foo::${'s'} !== null) {
		assertType('string', Foo::${'s'});
		assertType('string', Foo::$s);
	}
}

function narrowNullsafe(?Foo $obj): void
{
	if ($obj?->n !== null) {
		assertType('string', $obj->n);
		assertType('string', $obj->{'n'});
	}
}
