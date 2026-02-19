<?php declare(strict_types = 1);

namespace Bug11463b;

/**
 * @phpstan-type Foo 'foo'
 *
 * @phpstan-import-type Bar from BarType
 * @phpstan-import-type Baz from BazType
 */
class FooType
{
	/**
	 * @param Bar $bar
	 */
	public function foo(string $bar): void {}

	/**
	 * @param Baz $bar
	 */
	public function baz(string $bar): void {}
}

/**
 * @phpstan-import-type Foo from FooType
 *
 * @phpstan-type Bar 'bar'
 * @phpstan-import-type Baz from BazType
 */
class BarType {
	/**
	 * @param Foo $foo
	 */
	public function bar($foo): string { return $foo; }

	/**
	 * @param Baz $bar
	 */
	public function baz(string $bar): void {}
}

/**
 * @phpstan-type Baz 'baz'
 */
class BazType {
}
