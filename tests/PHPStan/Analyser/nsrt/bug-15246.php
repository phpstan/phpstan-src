<?php declare(strict_types = 1);

namespace Bug15246;

use function PHPStan\Testing\assertType;

/** @return array<mixed> */
function constantValue(): array
{
	$x = 0;
	$a = [1, 2, &$x];
	$x = 5;
	assertType('array{1, 2, 5}', $a);
	return $a;
}

/** @return array<mixed> */
function nonConstantValue(int $x): array
{
	$a = [1, 2, &$x];
	$x = 5;
	assertType('array{1, 2, 5}', $a);
	return $a;
}

/**
 * @param 0|1 $x
 * @return array<mixed>
 */
function finiteUnion(int $x): array
{
	$a = [&$x];
	$x = 5;
	assertType('array{5}', $a);
	return $a;
}

/** @return array<mixed> */
function stringValue(): array
{
	$x = 'a';
	$a = ['k' => &$x];
	$x = 'b';
	assertType("array{k: 'b'}", $a);
	return $a;
}

/** @return array<mixed> */
function nested(): array
{
	$x = 0;
	$a = [[1, &$x]];
	$x = 5;
	assertType('array{array{1, 5}}', $a);
	return $a;
}

/** @return array<mixed> */
function readingBack(): array
{
	$x = 0;
	$a = [1, 2, &$x];
	$x = 5;
	assertType('5', $a[2]);
	assertType('1', $a[0]);
	return $a;
}

/** @return array<mixed> */
function boolValue(): array
{
	$x = false;
	$a = [&$x];
	$x = true;
	assertType('array{true}', $a);
	return $a;
}

/** @return array<mixed> */
function floatValue(): array
{
	$x = 1.0;
	$a = [&$x];
	$x = 2.5;
	assertType('array{2.5}', $a);
	return $a;
}

/** @return array<mixed> */
function arrayValue(): array
{
	$x = [];
	$a = [&$x];
	$x = [1];
	assertType('array{array{1}}', $a);
	return $a;
}

/** @return array<mixed> */
function twoRefs(): array
{
	$x = 0;
	$y = 'a';
	$a = [&$x, &$y];
	$x = 5;
	assertType("array{5, 'a'}", $a);
	$y = 'b';
	assertType("array{5, 'b'}", $a);
	return $a;
}

/** @return array<mixed> */
function widerType(): array
{
	$x = 0;
	$a = [&$x];
	$x = doFoo();
	assertType("array{'a'|'b'}", $a);
	return $a;
}

/** @return 'a'|'b' */
function doFoo(): string
{
	return 'a';
}

/** @return array<mixed> */
function conditionalWrite(bool $c): array
{
	$x = 0;
	$a = [1, &$x];
	if ($c) {
		$x = 5;
	}
	assertType('array{1, 0|5}', $a);
	return $a;
}

/** @return array<mixed> */
function writtenInLoop(): array
{
	$x = 0;
	$a = [&$x];
	for ($i = 0; $i < 3; $i++) {
		$x = $i;
	}
	assertType('array{int<0, 2>}', $a);
	return $a;
}

/** @return array<mixed> */
function deeplyNested(): array
{
	$x = 0;
	$a = [[[&$x]]];
	$x = 5;
	assertType('array{array{array{5}}}', $a);
	return $a;
}

/** @return array<mixed> */
function otherOffsetWrittenFirst(): array
{
	$x = 0;
	$a = [1, &$x];
	$a[0] = 'z';
	$x = 5;
	assertType("array{'z', 5}", $a);
	return $a;
}

/** @return array<mixed> */
function writeThroughTheArray(): array
{
	$x = 0;
	$a = [1, 2, &$x];
	$a[2] = 9;
	assertType('9', $x);
	assertType('array{1, 2, 9}', $a);
	return $a;
}

/** @return array<mixed> */
function nonConstantKeyInLiteral(int $k): array
{
	$x = 0;
	$a = [$k => 1, &$x];
	$x = 5;
	assertType('array{0|5, ...<int, 1|5>}', $a);
	return $a;
}

/** @return array<mixed> */
function refUsedInTwoLiterals(): array
{
	$x = 0;
	$a = [&$x];
	$b = ['k' => &$x];
	$x = 5;
	assertType('array{5}', $a);
	assertType('array{k: 5}', $b);
	return [$a, $b];
}

/** @return array<mixed> */
function refChainedThroughVariable(): array
{
	$x = 0;
	$y = &$x;
	$a = [&$y];
	$x = 5;
	assertType('array{5}', $a);
	assertType('5', $y);
	return $a;
}

/** @return array<mixed> */
function sameRefTwice(): array
{
	$x = 0;
	$a = [&$x, &$x];
	$x = 5;
	assertType('array{5, 5}', $a);
	return $a;
}

/** @return array<mixed> */
function narrowingDoesNotWrite(): array
{
	$x = 0;
	$a = [&$x];
	if ($x === 0) {
		assertType('array{0}', $a);
	}
	return $a;
}
