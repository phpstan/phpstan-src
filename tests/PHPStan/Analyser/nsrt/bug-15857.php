<?php declare(strict_types = 1);

namespace Bug15857;

use function PHPStan\Testing\assertType;

abstract class Target {}
final class T1 extends Target {}
final class T2 extends Target {}
final class T3 extends Target {}
final class T4 extends Target {}
final class T5 extends Target {}
final class T6 extends Target {}
final class T7 extends Target {}

/** @param list<Target> $targets */
function take(array $targets): void {}

/** @param list<int> $items */
function f(array $items): void
{
	$targets = [];
	foreach ($items as $m) {
		if ($m === 1) { $targets[] = new T1(); }
		if ($m === 2) { $targets[] = new T2(); }
		if ($m === 3) { $targets[] = new T3(); }
		if ($m === 4) { $targets[] = new T4(); }
		if ($m === 5) { $targets[] = new T5(); }
		if ($m === 6) { $targets[] = new T6(); }
		if ($m === 7) { $targets[] = new T7(); }
	}
	assertType('list<Bug15857\T1|Bug15857\T2|Bug15857\T3|Bug15857\T4|Bug15857\T5|Bug15857\T6|Bug15857\T7>', $targets);
	take($targets);
}
