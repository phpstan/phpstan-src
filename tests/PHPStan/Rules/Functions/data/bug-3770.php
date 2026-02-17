<?php

namespace Bug3770;

/** @var array<int, int> $numbers */
$numbers = [];

array_map(
	function ($value) {
		return $value;
	},
	$numbers,
);

array_map(
	/** @param 1|2|3 $value */
	function ($value) {
		return $value;
	},
	$numbers,
);

array_map(
	/** @param 1|2|3 $value */
	fn($value) => $value,
	$numbers,
);
