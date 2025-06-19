<?php

namespace Bug13067;

function process(bool $real): void
{
	$printSum = function(float $sum): void {};

	if ($real) {
		$printSum = function(float $sum): void {
			echo $sum . "\n";
		};
	}

	$sum = 3;
	$printSum($sum);
}

/**
 * @param pure-callable $pureCallable
 */
function process2(bool $real, callable $pureCallable, callable $callable): void
{
	$cb = $pureCallable;
	if ($real) {
		$cb = $callable;
	}

	$cb();
}
