<?php // lint >= 8.1

namespace FirstClassCallables;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	public function doFoo(string $foo): void
	{
		assertType('Closure(string): void', $this->doFoo(...));
		assertType('Closure(): void', self::doBar(...));
		assertType('Closure', self::$foo(...));
		assertType('Closure', $this->nonexistent(...));
		assertType('Closure', $this->$foo(...));
		assertType('Closure(string): int<0, max>', strlen(...));
		assertType('Closure(string): int<0, max>', 'strlen'(...));
		assertType('Closure', 'nonexistent'(...));
	}

	public static function doBar(): void
	{

	}

}

class GenericFoo
{

	/**
	 * @template T
	 * @param T $a
	 * @return T
	 */
	public function doFoo($a)
	{
		return $a;
	}

	public function doBar()
	{
		$f = $this->doFoo(...);
		assertType('1', $f(1));
		assertType('\'foo\'', $f('foo'));

		$g = \Closure::fromCallable([$this, 'doFoo']);
		assertType('1', $g(1));
		assertType('\'foo\'', $g('foo'));
	}

	public function doBaz()
	{
		$ref = new \ReflectionClass(\stdClass::class);
		assertType('class-string<stdClass>', $ref->getName());

		$f = $ref->getName(...);
		assertType('class-string<stdClass>', $f());

		$g = \Closure::fromCallable([$ref, 'getName']);
		assertType('class-string<stdClass>', $g());
	}

}

class NeverCallable
{

	public function doFoo()
	{
		$n = function (): never {
			throw new \Exception();
		};

		if (rand(0, 1)) {
			$n();
		} else {
			$foo = 1;
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}

	public function doBar()
	{
		$n = function (): never {
			throw new \Exception();
		};

		if (rand(0, 1)) {
			$n(...);
		} else {
			$foo = 1;
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}

	/**
	 * @param callable(): never $n
	 */
	public function doBaz(callable $n): void
	{
		if (rand(0, 1)) {
			$n();
		} else {
			$foo = 1;
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}

}
