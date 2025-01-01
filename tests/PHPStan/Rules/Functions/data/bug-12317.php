<?php declare(strict_types = 1);

namespace Bug12317;

class Uuid {
	public function __construct(public string $uuid) {}
	public function __toString() { return $this->uuid; }
}

class HelloWorld
{
	/**
	 * @param list<Uuid> $a
	 *
	 * @return list<string>
	 */
	public function sayHello(array $a): array
	{
		$b = array_map(
			array: $a,
			callback: static fn(Uuid $c): string => (string) $c,
		);

		return $b;
	}
}
