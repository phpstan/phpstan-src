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
	$func3 = function() use ($container3): int {
		foreach ($container3->getItems() as $item) {}
		return 1;
	};

	$container3->setItems(['foo']);

	$a3 = $func3();
}

// Nullsafe property fetch
$container4 = new Foo();
$container4->items = [];

$func4 = function() use ($container4): int {
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

$func5 = function(): int {
	foreach (Baz::$items as $item) {}
	return 1;
};

Baz::$items[] = '1';

$a5 = $func5();

// Static method call
Baz::setItems([]);
if (Baz::getItems() === []) {
	$func6 = function(): int {
		foreach (Baz::getItems() as $item) {}
		return 1;
	};

	Baz::setItems(['foo']);

	$a6 = $func6();
}
