<?php declare(strict_types = 1);

namespace Bug14100;

use function PHPStan\Testing\assertType;

interface Foo {}
interface Bar {}
interface Baz {}

class HelloWorld
{
	/** @param resource|object $conn */
	public function ternaryAssertInstanceof($conn): void
	{
		assert(rand(0, 1) ? $conn instanceof Foo : $conn instanceof Bar);
		assertType('Bug14100\Bar|Bug14100\Foo', $conn);
	}

	/** @param resource|object $conn */
	public function ifElseAssertInstanceof($conn): void
	{
		if (rand(0, 1)) {
			assert($conn instanceof Foo);
		} else {
			assert($conn instanceof Bar);
		}
		assertType('Bug14100\Bar|Bug14100\Foo', $conn);
	}

	/** @param resource|object $conn */
	public function ternaryAssertThreeBranches($conn): void
	{
		assert(rand(0, 2) === 0 ? $conn instanceof Foo : (rand(0, 2) === 1 ? $conn instanceof Bar : $conn instanceof Baz));
		assertType('Bug14100\Bar|Bug14100\Baz|Bug14100\Foo', $conn);
	}

	/** @param mixed $val */
	public function ternaryAssertScalar($val): void
	{
		assert(rand(0, 1) ? is_string($val) : is_int($val));
		assertType('int|string', $val);
	}

	/**
	 * Short ternary syntax (Elvis operator): $a ?: $b
	 *
	 * @param resource|object $conn
	 */
	public function shortTernaryAssert($conn): void
	{
		assert($conn instanceof Foo ?: $conn instanceof Bar);
		assertType('Bug14100\Bar|Bug14100\Foo', $conn);
	}
}
