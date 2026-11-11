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
		assertType('static-Closure(): void', self::doBar(...));
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

/**
 * @method static string magicStatic(int $i)
 * @method string magic(int $i)
 */
class MagicCallables
{

	/**
	 * @param array<mixed> $args
	 */
	public static function __callStatic(string $name, array $args): mixed
	{
		return '';
	}

	/**
	 * @param array<mixed> $args
	 */
	public function __call(string $name, array $args): mixed
	{
		return '';
	}

	public function doFoo(): void
	{
		assertType('static-Closure(int): string', self::magicStatic(...));
		assertType('string', self::magicStatic(...)(1));
		assertType('Closure(int): string', $this->magic(...));
		assertType('string', $this->magic(...)(1));
	}

}

class MultiVariantCallables
{

	public function doFoo(): void
	{
		assertType('(Closure(): int)|(Closure(int, int): int)', mt_rand(...));
		assertType('(Closure(string): (non-empty-string|false))|(Closure(string, string): (non-empty-string|false))', strtok(...));
		assertType('int<1, 6>', mt_rand(...)(1, 6));
	}

}

class SecondLevelCallables
{

	/**
	 * @template T
	 * @param T $a
	 * @return T
	 */
	public function identity($a)
	{
		return $a;
	}

	public function doFoo(): void
	{
		$f = $this->identity(...);
		$g = $f(...);
		assertType('Closure<T of mixed>(T): T', $g);
		assertType('1', $g(1));
		$h = $g(...);
		assertType("'x'", $h('x'));
	}

}

class NewCallable
{

	public function doFoo(): void
	{
		assertType('*ERROR*', new \stdClass(...));
	}

}

class CallbackCallables
{

	public function sum(int $carry, int $item): int
	{
		return $carry + $item;
	}

	/**
	 * @param list<string> $strings
	 * @param list<int> $ints
	 */
	public function doFoo(array $strings, array $ints): void
	{
		usort($strings, strcmp(...));
		assertType('list<string>', $strings);
		assertType('int', array_reduce($ints, $this->sum(...), 0));
		assertType('lowercase-string&non-falsy-string', call_user_func_array(str_replace(...), [' ', '-', 'a b']));
		assertType('3', call_user_func(strlen(...), 'abc'));
	}

}

class BindCallables
{

	public static function doStatic(): int
	{
		return 1;
	}

	public function doInstance(): int
	{
		return 1;
	}

	public function doFoo(object $o): void
	{
		assertType('((Closure(string): int<0, max>)|null)', strlen(...)->bindTo($o));
		assertType('null', self::doStatic(...)->bindTo($o));
		assertType('null', \Closure::bind(self::doStatic(...), $o));
		assertType('static-Closure(): int', self::doStatic(...)->bindTo(null));
		assertType('((Closure(): int)|null)', $this->doInstance(...)->bindTo($o));
	}

	public function doInvoke(): void
	{
		$c = function (int $a, string $b, ?float $c = null): int {
			return 1;
		};
		assertType('1', $c->__invoke(1, 'x'));
		assertType('Closure(int, string, float|null=): 1', $c->__invoke(...));
		assertType('((Closure(int, string, float|null=): 1)|null)', $c->__invoke(...)->bindTo(null));
	}

}
