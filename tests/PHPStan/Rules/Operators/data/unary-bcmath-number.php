<?php

namespace UnaryBcMathNumber;

use BcMath\Number;

function testUnaryMinus(Number $x): void {
	-$x;
}

function testUnaryPlus(Number $x): void {
	+$x;
}

function testNestedUnary(Number $x): void {
	var_dump(+(-$x));
}

function testBitwiseNot(Number $x): void {
	~$x;
}
