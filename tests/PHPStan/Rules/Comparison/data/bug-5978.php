<?php declare(strict_types = 1);

namespace Bug5969;

function hash(string $input) : string {
	$hash = password_hash($input, PASSWORD_DEFAULT);
	if ($hash === false || $hash === null) {
		throw new \Exception("Could not generate hash");
	}

	return $hash;
}
