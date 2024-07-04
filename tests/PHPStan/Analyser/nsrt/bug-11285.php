<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11285;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template U
 */
class Templated
{

	/**
	 * @param callable(T|U): void $callback
	 */
	public function compare(callable $callback): void {

	}
}

class ClassA {}
class ClassB {}


class Test
{
	/**
	 * @param Templated<ClassA, ClassB>|Templated<ClassA, ClassA> $t
	 */
	public function broken(
		Templated $t,
	): void
	{
		$t->compare(
			static function (ClassA|ClassB $crate): void {
				assertType('Bug11285\\ClassA|Bug11285\\ClassB', $crate);
			}
		);
	}

	/**
	 * @param Templated<ClassA, ClassB> $t
	 */
	public function working(
		Templated $t,
	): void
	{
		$t->compare(
			static function (ClassA|ClassB $crate): void {
				assertType('Bug11285\\ClassA|Bug11285\\ClassB', $crate);
			}
		);
	}

}
