<?php // lint >= 8.0

namespace NamedArgumentsMatchRule;

use Exception;

function (bool $a, bool $b): void {
	foreach ([1, 2, 3] as $v) {
		match (true) {
			$a => 1,
			$b => 2,
			default => 3,
		};
		new Exception('foo', 0, new Exception('prev'));
	}
};
