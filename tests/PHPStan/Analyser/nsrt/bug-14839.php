<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug14839;

use function PHPStan\Testing\assertType;

enum Foo
{

	case A;
	case B;

}

enum Bar: string
{

	case A = 'a';
	case B = 'b';

}

function test(Foo $foo, Bar $bar, \UnitEnum $u, \BackedEnum $b): void
{
	assertType("'A'|'B'", $foo->name);
	assertType("'A'|'B'", $bar->name);
	assertType("'a'|'b'", $bar->value);
	assertType('non-decimal-int-string&non-falsy-string', $u->name);
	assertType('non-decimal-int-string&non-falsy-string', $b->name);
	// `value` stays as its native `int|string`: unlike a case name, a backing value may legitimately be "" or "0".
	assertType('int|string', $b->value);
}

/**
 * @template T of \UnitEnum
 * @param T $enum
 */
function testTemplate($enum): void
{
	assertType('non-decimal-int-string&non-falsy-string', $enum->name);
}
