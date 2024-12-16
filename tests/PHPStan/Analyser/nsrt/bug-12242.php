<?php // lint >= 7.4

namespace Bug12242;

use function PHPStan\Testing\assertType;

function foo(string $str): void
{
	$regexp = '/
		# (
		([\d,]*)
		# )
	/x';
	if (preg_match($regexp, $str, $match)) {
		assertType('array{string, string}', $match);
	}
}
