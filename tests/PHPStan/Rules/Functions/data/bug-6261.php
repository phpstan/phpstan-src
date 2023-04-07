<?php declare(strict_types = 1);

namespace Bug6261;

function needs_int(int $x) : void {}

function () {
	$x = filter_input(INPUT_POST, 'row_id', FILTER_VALIDATE_INT);

	if($x === false || $x === null) {
		die("I expected a numeric string!\n");
	}

	needs_int($x);
};
