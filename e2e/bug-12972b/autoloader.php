<?php

require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function($class) {
	if ($class === \other12972\MyClass::class) {
		throw new LogicException('this should not happen');
	}
});
