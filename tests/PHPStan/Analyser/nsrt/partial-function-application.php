<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(int $a, string $b): string
	{
		return $b . $a;
	}

	public static function doBar(int $a, int ...$rest): int
	{
		return $a;
	}

}

/**
 * @param list<string> $strings
 */
function doFoo(Foo $foo, string $name, array $strings): void
{
	assertType('Closure', str_replace(' ', '-', ?));
	assertType('Closure', $foo->doFoo(1, ?));
	assertType('Closure', $foo->doFoo(b: ?, a: 1));
	assertType('Closure', Foo::doBar(?, ...));
	assertType('Closure', $name(?));
	assertType('Closure', strlen('abc', ...));
	assertType('Closure', array_map(strtoupper(...), ?));
	assertType('list', array_map(strtoupper(?), $strings));

	$i = 0;
	$f = str_repeat('a', $i++, ...);
	assertType('1', $i);

	$f = $foo->doFoo(...)(?, 'b');
	assertType('Closure', $f);
}
