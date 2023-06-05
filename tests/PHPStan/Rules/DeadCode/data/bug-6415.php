<?php declare(strict_types = 1);

namespace Bug6415;

class Foo
{
	private const VALUES = [1,2,3];

	/** @param value-of<self::VALUES> $value */
	public function bar(int $value): void {
	}

	/** @param key-of<self::VALUES> $value */
	public function baz(int $value): void {
	}
}
