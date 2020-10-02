<?php
/**
 * @param array<int, string> $test
 */
function foo(array $test): void
{
	array_map(
		function(stdClass $a) {
			return $a;
		},
		$test
	);

	array_reduce($test, function (string $carry, int $a){
		return '';
	}, '');
}


$cb = fn(callable  $b) => $b();

array_filter(['1', '2', '3'], $cb);
