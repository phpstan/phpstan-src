<?php // lint >= 8.0

namespace MatchArmTypeCheckCall;

function doFoo(int $i, int $y): string
{
	return match ($i) {
		is_int($y) => 'a',
		default => 'b',
	};
}

function doBar(int $y): string
{
	return match (true) {
		is_int($y) => 'a',
		default => 'b',
	};
}
