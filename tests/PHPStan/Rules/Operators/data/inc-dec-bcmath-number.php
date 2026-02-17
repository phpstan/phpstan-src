<?php

namespace IncDecBcMathNumber;

use BcMath\Number;

function testPreInc(Number $x): void {
	++$x;
}

function testPostInc(Number $x): void {
	$x++;
}

function testPreDec(Number $x): void {
	--$x;
}

function testPostDec(Number $x): void {
	$x--;
}
