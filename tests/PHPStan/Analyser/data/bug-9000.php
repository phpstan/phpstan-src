<?php // onlyif PHP_VERSION_ID >= 80100

namespace Bug9000;

use function PHPStan\Testing\assertType;

enum A:string {
	case A = "A";
	case B = "B";
	case C = "C";
}

const A_ARRAY = [
	'A' => A::A,
	'B' => A::B,
];

/**
 * @param string $key
 * @return value-of<A_ARRAY>
 */
function testA(string $key): A
{
	return A_ARRAY[$key];
}

function (): void {
	$test = testA('A');
	assertType('Bug9000\A::A|Bug9000\A::B', $test);
	assertType("'A'|'B'", $test->value);
};
