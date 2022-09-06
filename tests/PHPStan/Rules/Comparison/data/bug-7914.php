<?php declare(strict_types = 1);

namespace Bug7914;

function foo(string $s): void
{
	if (is_numeric($s)) {
		if (ctype_digit($s)) {
			echo "I'm all-digit string";
		} else {
			echo "I'm a not all-digit string";
		}
	}
}
