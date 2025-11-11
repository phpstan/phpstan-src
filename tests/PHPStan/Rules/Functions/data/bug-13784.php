<?php declare(strict_types = 1);

namespace Bug13784;

function ok(): int {
	return strlen(number_format(15, 0, '', ''));
}

/**
 * @param numeric-string $str
 */
function ok2(string $str): int {
	return strlen($str);
}

function fail(): int {
	return strlen(ltrim(number_format(15, 0, '', ''), '-'));
}

function fail2(): int {
	return strlen(trim(number_format(15, 0, '', ''), '-'));
}
