<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10345;

use function PHPStan\Testing\assertType;

$container = new \stdClass();
$container->items = [];

assertType('stdClass', $container);
assertType('array{}', $container->items);
$func = function() use ($container): int {
	assertType('stdClass', $container);
	assertType('mixed', $container->items);
	foreach ($container->items as $item) {
	}
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

assertType('Bug10345\Foo', $container2);
assertType('array{}', $container2->items);
$func2 = function() use ($container2): int {
	assertType('Bug10345\Foo', $container2);
	assertType('list<string>', $container2->items);
	foreach ($container2->items as $item) {}
	return 1;
};

$container2->items[] = '1';

$a2 = $func2();

class Bar {
	/** @var list<string> */
	private array $items = [];

	/** @return list<string> */
	public function getItems(): array
	{
		return $this->items;
	}

	/** @param list<string> $items */
	public function setItems(array $items): void
	{
		$this->items = $items;
	}
}

$container3 = new Bar();
if ($container3->getItems() === []) {
	assertType('Bug10345\Bar', $container3);
	assertType('array{}', $container3->getItems());
	$func3 = function() use ($container3): int {
		assertType('Bug10345\Bar', $container3);
		assertType('list<string>', $container3->getItems());
		foreach ($container3->getItems() as $item) {}
		return 1;
	};

	$container3->setItems(['foo']);

	$a3 = $func3();
}

// Nullsafe property fetch
$container4 = new Foo();
$container4->items = [];

assertType('Bug10345\Foo', $container4);
assertType('array{}', $container4->items);
$func4 = function() use ($container4): int {
	assertType('Bug10345\Foo', $container4);
	assertType('list<string>', $container4->items);
	foreach ($container4?->items as $item) {}
	return 1;
};

$container4->items[] = '1';

$a4 = $func4();

// Static property access
class Baz {
	/** @var list<string> */
	public static array $items = [];

	/** @return list<string> */
	public static function getItems(): array
	{
		return self::$items;
	}

	/** @param list<string> $items */
	public static function setItems(array $items): void
	{
		self::$items = $items;
	}
}

Baz::$items = [];

assertType('array{}', Baz::$items);
$func5 = function(): int {
	assertType('list<string>', Baz::$items);
	foreach (Baz::$items as $item) {}
	return 1;
};

Baz::$items[] = '1';

$a5 = $func5();

// Static method call
Baz::setItems([]);
if (Baz::getItems() === []) {
	assertType('array{}', Baz::getItems());
	$func6 = function(): int {
		assertType('list<string>', Baz::getItems());
		foreach (Baz::getItems() as $item) {}
		return 1;
	};

	Baz::setItems(['foo']);

	$a6 = $func6();
}

// Immediately invoked closure should keep the type
$container7 = new \stdClass();
$container7->items = [];

assertType('stdClass', $container7);
assertType('array{}', $container7->items);
$result7 = array_map(
	function() use ($container7): int {
		assertType('stdClass', $container7);
		assertType('array{}', $container7->items);
		foreach ($container7->items as $item) {
		}
		return 1;
	},
	[1, 2, 3],
);

// Immediately invoked closure with declared property should also keep the type
$container8 = new Foo();
$container8->items = [];

assertType('Bug10345\Foo', $container8);
assertType('array{}', $container8->items);
$result8 = array_map(
	function() use ($container8): int {
		assertType('Bug10345\Foo', $container8);
		assertType('array{}', $container8->items);
		foreach ($container8->items as $item) {}
		return 1;
	},
	[1, 2, 3],
);

// IIFE should keep the type
$container9 = new \stdClass();
$container9->items = [];

assertType('stdClass', $container9);
assertType('array{}', $container9->items);
$result9 = (function() use ($container9): int {
	assertType('stdClass', $container9);
	assertType('array{}', $container9->items);
	foreach ($container9->items as $item) {
	}
	return 1;
})();
