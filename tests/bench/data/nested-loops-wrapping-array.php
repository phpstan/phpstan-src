<?php declare(strict_types = 1);

/*
 * instructions: 169.2M
 * cycles: 231.1M
 * memory: 772.2KiB
 */

namespace BenchNestedLoopsWrappingArray;

/**
 * Every loop wraps the value in a single-key array. Merging the loop
 * scopes unions single-key arrays nested in each other, and unioning
 * their values more than once per merge made it exponential in the
 * nesting depth.
 */
function run(): void
{
	$x = 0;
	while (rand(0, 1)) {
		$x = [$x];
		while (rand(0, 1)) {
			$x = [$x];
			while (rand(0, 1)) {
				$x = [$x];
				while (rand(0, 1)) {
					$x = [$x];
				}
			}
		}
	}

	var_dump($x);
}
