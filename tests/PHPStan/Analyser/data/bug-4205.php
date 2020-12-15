<?php

namespace Bug4205;

use function PHPStan\Analyser\assertType;

function () {
	$result = set_error_handler(function() {}, E_ALL);
	assertType('(callable(): mixed)|null', $result);
};
