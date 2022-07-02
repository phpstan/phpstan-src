<?php declare(strict_types = 1);

namespace Bug7257;

function rangesInConflict(): bool
{
	$ranges1 = array_filter([
		(int) rand(0,10),
		(int) rand(0,10),
		(int) rand(0,10),
		(int) rand(0,10),
	]);

	$ranges2 = array_filter([
		(int) rand(0,10),
		(int) rand(0,10),
		(int) rand(0,10),
		(int) rand(0,10),
	]);

	$sorted = array_filter([
		(int) rand(0,10),
		(int) rand(0,10),
		(int) rand(0,10),
		(int) rand(0,10),
	]);

	sort($sorted);

	return !($sorted === $ranges1 || $sorted === $ranges2);
}
