<?php

namespace Discussion10454;

use stdClass;

function (): void {
	$arr = ['foo', 'bar', 'baz', 'qux', 'quux'];

	print_r(
		array_filter(
			$arr,
			function (string $foo): stdClass
			{
				return new stdClass();
			}
		)
	);

	print_r(
		array_filter(
			$arr,
			function (string $foo)
			{
				return new stdClass();
			}
		)
	);
};
