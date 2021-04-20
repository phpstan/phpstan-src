<?php

namespace IncDecInConditions;

use function PHPStan\Testing\assertType;

function incLeft(int $a, int $b, int $c, int $d): void
{
	if (++$a < 0) {
		assertType('int<min, -1>', $a);
	} else {
		assertType('int<0, max>', $a);
	}
	if (++$b <= 0) {
		assertType('int<min, 0>', $b);
	} else {
		assertType('int<1, max>', $b);
	}
	if ($c++ < 0) {
		assertType('int<min, 0>', $c);
	} else {
		assertType('int<1, max>', $c);
	}
	if ($d++ <= 0) {
		assertType('int<min, 1>', $d);
	} else {
		assertType('int<2, max>', $d);
	}
}

function incRight(int $a, int $b, int $c, int $d): void
{
	if (0 < ++$a) {
		assertType('int<1, max>', $a);
	} else {
		assertType('int<min, 0>', $a);
	}
	if (0 <= ++$b) {
		assertType('int<0, max>', $b);
	} else {
		assertType('int<min, -1>', $b);
	}
	if (0 < $c++) {
		assertType('int<2, max>', $c);
	} else {
		assertType('int<min, 1>', $c);
	}
	if (0 <= $d++) {
		assertType('int<1, max>', $d);
	} else {
		assertType('int<min, 0>', $d);
	}
}

function decLeft(int $a, int $b, int $c, int $d): void
{
	if (--$a < 0) {
		assertType('int<min, -1>', $a);
	} else {
		assertType('int<0, max>', $a);
	}
	if (--$b <= 0) {
		assertType('int<min, 0>', $b);
	} else {
		assertType('int<1, max>', $b);
	}
	if ($c-- < 0) {
		assertType('int<min, -2>', $c);
	} else {
		assertType('int<-1, max>', $c);
	}
	if ($d-- <= 0) {
		assertType('int<min, -1>', $d);
	} else {
		assertType('int<0, max>', $d);
	}
}

function decRight(int $a, int $b, int $c, int $d): void
{
	if (0 < --$a) {
		assertType('int<1, max>', $a);
	} else {
		assertType('int<min, 0>', $a);
	}
	if (0 <= --$b) {
		assertType('int<0, max>', $b);
	} else {
		assertType('int<min, -1>', $b);
	}
	if (0 < $c--) {
		assertType('int<0, max>', $c);
	} else {
		assertType('int<min, -1>', $c);
	}
	if (0 <= $d--) {
		assertType('int<-1, max>', $d);
	} else {
		assertType('int<min, -2>', $d);
	}
}
