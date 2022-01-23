<?php

namespace Bug1849;

function (string $type): void {
	$func = 'is_' . $type;

	if (function_exists($func)) {
		$func([]);
	}
};
