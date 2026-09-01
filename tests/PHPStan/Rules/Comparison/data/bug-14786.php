<?php declare(strict_types = 1);

namespace Bug14786;

// example inspired by nikic/PHP-Parser ParserAbstract::parseNumber()
/** @param decimal-int-string $str */
function parseNumber(string $str): int|float
{
	// big decimal-int-strings overflow PHP_INT_MAX and become float,
	// so is_int() is no longer always true.
	$num = +$str;
	if (!is_int($num)) {
		return $num;
	}

	return $num;
}
