<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationReplay;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $a
 * @param T $b
 * @return T
 */
function pick(mixed $a, mixed $b): mixed
{
	return $a;
}

/**
 * @param array<string, int> $arr
 */
function bound(\DateTimeImmutable $d, array $arr, string $s): void
{
	$rp = str_replace(' ', '-', ?);
	assertType('lowercase-string&non-falsy-string', $rp('a b'));
	assertType('lowercase-string&non-falsy-string', $rp(subject: 'a b'));

	$rn = str_replace(search: ' ', replace: '-', ...);
	assertType('lowercase-string&non-falsy-string', $rn('a b'));

	$fmt = sprintf('%s-%s', ?, ...);
	assertType("'a-b'", $fmt('a', 'b'));

	$pm = preg_match('/(a)(b)?/', ?, ...);
	$pm('ab', $mm);
	assertType("array{}|array{0: non-falsy-string, 1: 'a', 2?: 'b'}", $mm);

	$has = array_key_exists('a', ?);
	if ($has($arr)) {
		assertType("non-empty-array<string, int>&hasOffset('a')", $arr);
	}

	$in = in_array(?, ['a', 'b'], true);
	if ($in($s)) {
		assertType("'a'|'b'", $s);
	}

	$p = pick(1, ?);
	assertType("1|'x'", $p('x'));

	$mod = $d->modify(?);
	assertType('DateTimeImmutable', $mod('+1 day'));
}

/**
 * @param list<1|2> $ints
 */
function unions(bool $b, array $ints): void
{
	$p = $b ? str_replace(' ', '-', ?) : str_replace(' ', '_', ?);
	assertType('lowercase-string&non-falsy-string', $p('a b'));

	$q = str_repeat('a', ?);
	foreach ($ints as $i) {
		$q = str_repeat((string) $i, ?);
	}
	assertType("'11'|'22'|'aa'", $q(2));
}
