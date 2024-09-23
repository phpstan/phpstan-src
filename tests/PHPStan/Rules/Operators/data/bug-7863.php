<?php

namespace Bug7863;

function ($mixed, array $arr) {
	if (is_array($mixed)) {
		return;
	}
	// mixed~array + array
	$x = $mixed + $arr;
};
