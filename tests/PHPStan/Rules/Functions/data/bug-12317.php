<?php declare(strict_types = 1);

namespace Bug12317;

class Uuid {
	public function __construct(public string $uuid) {}
	public function __toString() { return $this->uuid; }
}

class HelloWorld
{
	/**
	 * @param list<Uuid> $arr
	 */
	public function sayHello(array $arr): void
	{
		$callback = static fn(Uuid $uuid): string => (string) $uuid;

		// ok
		array_map(array: $arr, callback: $callback);
		array_map(callback: $callback, array: $arr);
		array_map($callback, $arr);
		array_map($callback, array: $arr);
		array_map(static fn (Uuid $u1, Uuid $u2): string => (string) $u1, $arr, $arr);

		// should be reported
		$invalidCallback = static fn(string $uuid): string => $uuid;
		array_map($invalidCallback, $arr);
		array_map(array: $arr, callback: $invalidCallback);
	}
}
