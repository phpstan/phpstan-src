<?php

namespace Bug7075;

/** @param int<0, 100> $b */
function foo(int $b): void {
	if ($b > 100) throw new \Exception("bad");
	print "ok";
}

/**
 * @param int<1,max> $number
 */
function foo2(int $number): void {
	if ($number < 1) {
		throw new \Exception('Number cannot be less than 1');
	}
}
