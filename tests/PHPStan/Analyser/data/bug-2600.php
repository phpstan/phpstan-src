<?php // onlyif PHP_VERSION_ID < 80000

namespace Bug2600;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param mixed ...$x
	 */
	public function doFoo($x = null) {
		$args = func_get_args();
		assertType('mixed', $x);
		assertType('list<mixed>', $args);
	}

	/**
	 * @param mixed ...$x
	 */
	public function doBar($x = null) {
		assertType('mixed', $x);
	}

	/**
	 * @param mixed $x
	 */
	public function doBaz(...$x) {
		assertType('list<mixed>', $x);
	}

	/**
	 * @param mixed ...$x
	 */
	public function doLorem(...$x) {
		assertType('list<mixed>', $x);
	}

	public function doIpsum($x = null) {
		$args = func_get_args();
		assertType('mixed', $x);
		assertType('list<mixed>', $args);
	}
}

class Bar
{
	/**
	 * @param string ...$x
	 */
	public function doFoo($x = null) {
		$args = func_get_args();
		assertType('string|null', $x);
		assertType('list<mixed>', $args);
	}

	/**
	 * @param string ...$x
	 */
	public function doBar($x = null) {
		assertType('string|null', $x);
	}

	/**
	 * @param string $x
	 */
	public function doBaz(...$x) {
		assertType('list<string>', $x);
	}

	/**
	 * @param string ...$x
	 */
	public function doLorem(...$x) {
		assertType('list<string>', $x);
	}
}

function foo($x, string ...$y): void
{
	assertType('mixed', $x);
	assertType('list<string>', $y);
}

function ($x, string ...$y): void {
	assertType('mixed', $x);
	assertType('list<string>', $y);
};
