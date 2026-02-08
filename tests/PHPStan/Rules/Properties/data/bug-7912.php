<?php

namespace Bug7912;

class A
{
	/** @var int<0,1> */
	public int $has = 0;

	/** @var int<0,1> */
	public int $not = 0;
}

class B
{
	/** @var int<0,1> */
	public int $has = 1;
}

$a = new A();
$b = new B();

// The following versions throw an error, even though | between 0,1 will always be 0,1
$a->has |= $b->has;
$a->has = $a->has | $b->has;

// The following versions don't:
$a->has = 0 | 1;
$a->has |= 1;

$int = 1;
$a->has |= $int;

// This properly errors:
$a->has |= 999;

// And these all work:
/** @var int<0,1> */
$c = 0;
/** @var int<0,1> */
$e = 1;
$c |= $e;
$c |= $a->has;
