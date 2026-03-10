<?php declare(strict_types = 1);

namespace Bug10345;

$container = new \stdClass();
$container->items = [];

$func = function() use ($container): int {
	foreach ($container->items as $item) {}
	return 1;
};

$container->items[] = '1';

$a = $func();
