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

class Foo {
	/** @var list<string> */
	public array $items = [];
}

$container2 = new Foo();
$container2->items = [];

$func2 = function() use ($container2): int {
	foreach ($container2->items as $item) {}
	return 1;
};

$container2->items[] = '1';

$a2 = $func2();
