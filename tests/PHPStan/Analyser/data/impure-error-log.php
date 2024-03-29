<?php

namespace ImpureErrorLog;

use function PHPStan\Testing\assertType;

function doFoo() {
	$message = 'foo';
	$logfile = 'bar/baz.txt';
	if (!error_log($message, 3, $logfile)) {
		assertType('bool', error_log($message, 3, $logfile));
	}
}

