<?php

namespace IncDecGmp;

use GMP;

function testPreInc(GMP $x): void {
	++$x;
}

function testPostInc(GMP $x): void {
	$x++;
}

function testPreDec(GMP $x): void {
	--$x;
}

function testPostDec(GMP $x): void {
	$x--;
}
