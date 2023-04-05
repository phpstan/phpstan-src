<?php

namespace Bug9062;

use function PHPStan\Testing\assertType;

/**
 * @property-read int|null $port
 * @property-write int|string|null $port
 */
class Foo {
	private ?int $port;

	public function __set(string $name, mixed $value): void {
		if ($name === 'port') {
			if ($value === null || is_int($value)) {
				$this->port = $value;
			} elseif (is_string($value) && strspn($value, '0123456789') === strlen($value)) {
				$this->port = (int) $value;
			} else {
				throw new \Exception("Property {$name} can only be a null, an int or a string containing the latter.");
			}
		} else {
			throw new \Exception("Unknown property {$name}.");
		}
	}

	public function __get(string $name): mixed {
		if ($name === 'port') {
			return $this->port;
		} else {
			throw new \Exception("Unknown property {$name}.");
		}
	}
}

function (): void {
	$foo = new Foo;
	$foo->port = "66";

	assertType('int|null', $foo->port);
};
