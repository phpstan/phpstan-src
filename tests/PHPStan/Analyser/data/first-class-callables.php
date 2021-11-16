<?php // lint >= 8.1

namespace FirstClassCallables;

use function PHPStan\Testing\assertType;

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
		assertType('int', $f(1));
		assertType('string', $f('foo'));

		$g = \Closure::fromCallable([$this, 'doFoo']);
		assertType('int', $g(1));
		assertType('string', $g('foo'));
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
