<?php declare(strict_types = 1);

namespace Bug14654;

use function PHPStan\Testing\assertType;

function foo(): void {
	$a = \ord('a');
	$b = \ord('b');

	assertType('int<0, 255>', $a);
	assertType('int<0, 255>', $b);
	assertType('int<0, 255>', $a ^ $b);
	assertType('int<0, 255>', $a | $b);
	assertType('int<0, 255>', $a & $b);

	/** @var int<0, 255> $c */
	$c = 0;
	assertType('int<0, 255>', $c ^ 42);
	assertType('int<0, 255>', 42 ^ $c);
	assertType('int<0, 255>', $c | 42);
	assertType('int<0, 42>', $c & 42);

	/** @var int<0, 20> $x */
	$x = 0;
	/** @var int<0, 20> $y */
	$y = 0;
	assertType('int<0, 31>', $x ^ $y);
	assertType('int<0, 31>', $x | $y);
	assertType('int<0, 20>', $x & $y);

	// AND with ranges of different sizes
	assertType('int<0, 20>', $a & $x);
	assertType('int<0, 20>', $x & $a);

	// Unbounded ranges stay int
	/** @var int<0, max> $unbounded */
	$unbounded = 0;
	assertType('int', $unbounded ^ $a);
	assertType('int', $unbounded | $a);

	// Negative ranges stay int for XOR/OR
	/** @var int<-10, 10> $signed */
	$signed = 0;
	assertType('int', $signed ^ $x);
	assertType('int', $signed | $x);

	// Bitwise NOT preserves range bounds
	assertType('int<-256, -1>', ~$a);
	assertType('int<-21, -1>', ~$x);

	/** @var int<min, 10> $minBounded */
	$minBounded = 0;
	assertType('int<-11, max>', ~$minBounded);

	/** @var int<-5, max> $maxBounded */
	$maxBounded = 0;
	assertType('int<min, 4>', ~$maxBounded);

	// Compound assignment operators
	/** @var int<0, 255> $d */
	$d = 0;
	$d &= $a;
	assertType('int<0, 255>', $d);
}
