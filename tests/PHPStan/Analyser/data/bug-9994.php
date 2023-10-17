<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug9994;

function (): void {

	$arr = [
		1,
		2,
		3,
		null,
	];


	var_dump(array_filter($arr, !is_null(...)));
};
