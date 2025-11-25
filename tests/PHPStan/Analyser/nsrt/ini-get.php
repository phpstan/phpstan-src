<?php

namespace IniGet;

use function PHPStan\Testing\assertType;

function doFoo() {
	assertType('string', ini_get("date.timezone"));
	assertType('string', ini_get("memory_limit"));
	assertType('numeric-string', ini_get("max_execution_time"));
	assertType('numeric-string', ini_get("max_input_time"));
	assertType('numeric-string', ini_get("default_socket_timeout"));
	assertType('numeric-string', ini_get("precision"));

	if (rand(1, 0)) {
		$x = ini_get("date.timezone");
	} else {
		$x = ini_get("max_execution_time");
	}
	assertType('string', $x);

	if (rand(1, 0)) {
		$key = 'unknown';
	} else {
		$key = "max_execution_time";
	}
	assertType('string|false', ini_get($key));
	assertType('string|false', ini_get('unknown'));

	if (PHP_VERSION_ID >= 80500) {
		assertType('string', ini_get("max_memory_limit"));
	} else {
		assertType('string|false', ini_get("max_memory_limit"));
	}
	if (PHP_VERSION_ID >= 80300) {
		assertType('string|false', ini_get("max_memory_limit"));
	}
	if (PHP_VERSION_ID < 80300) {
		assertType('string|false', ini_get("max_memory_limit"));
	}
}
