<?php declare(strict_types = 1);

namespace Bug11463;

/**
 * @phpstan-type Foo 'foo'
 *
 * @phpstan-import-type Bar from BarType
 */
class FooType
{
	/**
	 * @param Bar $bar
	 */
	public function foo(string $bar): void {}
}

/**
 * @phpstan-import-type Foo from FooType
 *
 * @phpstan-type Bar 'bar'
 */
class BarType {
	/**
	 * @param Foo $foo
	 */
	public function bar($foo): string { return $foo; }
}
