<?php

declare(strict_types = 1);

namespace Bug13713;

function debug(object $test): void {
	if ($test instanceof \stdClass) {
		echo var_export(\is_subclass_of($test, \stdClass::class, false), true) . \PHP_EOL;
	}
}

class test extends \stdClass {}
debug(new test);
