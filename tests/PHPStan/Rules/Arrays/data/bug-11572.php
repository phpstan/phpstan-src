<?php declare(strict_types=1);

namespace bug11572;

/**
 * @param string $s
 * @param numeric-string $numericS
 * @param literal-string $literalS
 * @param non-empty-string $nonEmptyS
 * @param non-falsy-string $nonFalsyS
 * @param non-falsy-string&literal-string $intersectedS
 */
function doFoo($s, $numericS, $literalS, $nonEmptyS, $nonFalsyS, $intersectedS): void
{
	$s[] = 'foo';
	$numericS[] = 'foo';
	$literalS[] = 'foo';
	$nonEmptyS[] = 'foo';
	$nonFalsyS[] = 'foo';
	$intersectedS[] = 'foo';
}

$string = returnString() . ' bar';
$string[] = 'foo';

function returnString(): string
{
	return 'baz';
}

class X {
	const XY ='ABC';

	function doFoo() {
		$s = X::XY;
		$s[] = 'foo';
	}
}

/**
 * @param int<3,4> $range
 */
function doInt(int $i, $range): void
{
	$i[] = 1;
	$range[] = 1;
}
