<?php declare(strict_types = 1);

namespace Bug14787;

use function PHPStan\Testing\assertType;

function reported(bool $a, bool $b, bool $c): void
{
	// After `!($a && $b && $c)` only the disjunction `!$a || !$b || !$c` is known.
	// Knowing `$c` is true does not make `$b` false (e.g. $a=false, $b=true, $c=true).
	if ($a && $b && $c) {
		return;
	}

	if ($c) {
		assertType('bool', $a);
		assertType('bool', $b);
	}

	// Both operand orders must behave the same.
	if ($c && $b) {
		assertType('true', $c);
		assertType('true', $b);
	}
	if ($b && $c) {
		assertType('true', $b);
		assertType('true', $c);
	}
}

function twoOperandsStillNarrow(bool $b, bool $c): void
{
	// A non-compound side keeps its sound narrowing: `!($b && $c)` with `$c` true
	// does imply `$b` is false.
	if ($b && $c) {
		return;
	}

	if ($c) {
		assertType('false', $b);
	}
}

function disjunctionOperandNarrows(bool $p, bool $q, bool $r): void
{
	// `!(($p || $q) && $r)` with `$r` true implies `!($p || $q)`, i.e. both false.
	// The negation of a disjunction is a conjunction, so this split is sound.
	if (($p || $q) && $r) {
		return;
	}

	if ($r) {
		assertType('false', $p);
		assertType('false', $q);
	}
}
