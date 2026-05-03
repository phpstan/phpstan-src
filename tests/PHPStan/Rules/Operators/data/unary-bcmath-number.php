<?php

namespace BcMathNumberUnaryOperators;

use BcMath\Number;

function testUnaryPlus(Number $x): void {
	+$x;
}

function testUnaryMinus(Number $x): void {
	-$x;
}

function testBitwiseNot(Number $x): void {
	~$x;
}
