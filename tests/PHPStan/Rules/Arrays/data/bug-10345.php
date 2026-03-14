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
