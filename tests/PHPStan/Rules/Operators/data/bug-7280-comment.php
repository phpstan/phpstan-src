<?php

namespace Bug7280Comment;

function doFoo() {
	/**
	 * @var array<int, int> $numbers
	 */
	$numbers = [1, 2];

	$sum = array_reduce(
		$numbers,
		fn ($curr, $n) => $curr + $n,
		0
	);

	if ($sum > 0) {
		//
	}
}
