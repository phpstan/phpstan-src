<?php

namespace Bug2499;

function (): void {
	$value = 123;

	if ($value = $_GET['name'] and is_string($value)) {
		echo $value;
	}
};
