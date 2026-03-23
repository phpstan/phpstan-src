<?php declare(strict_types = 1);

namespace Bug13577;

use function PHPStan\Testing\assertType;

class A {}
interface B {}

/**
 * @template T of A&B
 */
class Foo
{
	/**
	 * @param T $foo
	 */
	public function a($foo): void
	{
		assertType('T of Bug13577\A&Bug13577\B (class Bug13577\Foo, argument)', $foo);

		if (!$foo instanceof B) {
			throw new \Exception();
		}

		assertType('T of Bug13577\A&Bug13577\B (class Bug13577\Foo, argument)', $foo);

		$this->b($foo);
	}

	/**
	 * @param T $foo
	 */
	public function b($foo): void {}
}
