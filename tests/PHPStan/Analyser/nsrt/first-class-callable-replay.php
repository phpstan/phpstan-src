<?php // lint >= 8.1

declare(strict_types = 1);

namespace FirstClassCallableReplay;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param-out int $out
	 */
	public static function out(mixed &$out): void
	{
		$out = 1;
	}

}

/**
 * @param array<string, int> $arr
 */
function problems(\DateTimeImmutable $d, array $arr, string $s, mixed $x): void
{
	$r = str_replace(...);
	assertType('lowercase-string&non-falsy-string', $r(' ', '-', 'a b'));
	assertType('lowercase-string&non-falsy-string', $r(subject: 'a b', search: ' ', replace: '-'));
	assertType('3', strlen(...)('abc'));

	$a = [3, 1, 2];
	$sort = sort(...);
	$sort($a);
	assertType('non-empty-list<1|2|3>', $a);

	$pm = preg_match(...);
	$pm('/(a)(b)?/', 'ab', $mm);
	assertType("array{}|array{0: non-falsy-string, 1: 'a', 2?: 'b'}", $mm);

	$ake = array_key_exists(...);
	if ($ake('a', $arr)) {
		assertType("non-empty-array<string, int>&hasOffset('a')", $arr);
	}

	$isInt = is_int(...);
	if ($isInt($x)) {
		assertType('int', $x);
	}

	$out = Foo::out(...);
	$o = null;
	$out($o);
	assertType('int', $o);

	$m = $d->modify(...);
	assertType('DateTimeImmutable', $m('+1 day'));
}

function unions(bool $b, string $s): void
{
	$f = $b ? strtoupper(...) : strtoupper(...);
	assertType("'A'", $f('a'));

	$g = $b ? strtoupper(...) : strtolower(...);
	assertType("'A'|'a'", $g('a'));

	$plain = static fn (string $string): string => $string;
	$h1 = $b ? strtoupper(...) : $plain;
	$h2 = $b ? $plain : strtoupper(...);
	assertType("'a'|uppercase-string", $h1('a'));
	assertType("'a'|uppercase-string", $h2('a'));

	if ($b) {
		$i = strtoupper(...);
	} else {
		$i = strtolower(...);
	}
	assertType("'A'|'a'", $i('a'));

	$n = $b ? is_int(...) : is_numeric(...);
	$x = $s;
	if ($n($x)) {
		assertType('numeric-string', $x);
	}
}
