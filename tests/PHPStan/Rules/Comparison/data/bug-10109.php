<?php declare(strict_types = 1);

namespace Bug10109;

function simple(): void
{
	$x = 5;
	while (--$x > 0) {
	}

	if ($x === 0) {
		echo 'zero';
	}
}

function closerToRealCode(int $max): void
{
	$x = $max;
	while (--$x > 0) {
		doSomething($x);
	}

	if ($x === 0) {
		echo 'reached the end';
	}
}

function doSomething(int $x): void
{
}
