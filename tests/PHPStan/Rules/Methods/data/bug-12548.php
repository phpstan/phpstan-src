<?php declare(strict_types = 1);

namespace Bug12548;

class BaseSat extends \stdClass
{
	/**
	 * @template T of object
	 *
	 * @param T $object
	 *
	 * @phpstan-assert-if-true =static $object
	 */
	public static function assertInstanceOf(object $object): bool
	{
		return $object instanceof static;
	}

	/**
	 * @template T of object
	 *
	 * @param T $object
	 *
	 * @return (T is static ? T : static)
	 *
	 * @phpstan-assert static $object
	 */
	public static function assertInstanceOf2(object $object)
	{
		if (!$object instanceof static) {
			throw new \Error('Object is not an instance of static class');
		}

		return $object;
	}
}

class StdSat extends BaseSat
{
	public function foo(): void {}

	/**
	 * @template T of object
	 *
	 * @param T $object
	 *
	 * @return (T is static ? T : static)
	 *
	 * @phpstan-assert static $object
	 */
	public static function assertInstanceOf3(object $object)
	{
		if (!$object instanceof static) {
			throw new \Error('Object is not an instance of static class');
		}

		return $object;
	}
}

class TestCase
{
	private function createStdSat(): \stdClass
	{
		return new StdSat();
	}

	public function testAssertInstanceOf(): void
	{
		$o = $this->createStdSat();
		$o->foo(); // @phpstan-ignore method.nonObject (EXPECTED)

		$o = $this->createStdSat();
		if (StdSat::assertInstanceOf($o)) {
			$o->foo();
		}

		$o = $this->createStdSat();
		StdSat::assertInstanceOf2($o);
		$o->foo();

		$o = $this->createStdSat();
		StdSat::assertInstanceOf3($o);
		$o->foo();
	}
}
