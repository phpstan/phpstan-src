<?php // lint >= 8.1

declare(strict_types = 1);

namespace GeneratorNodeScopeResolverTest;

use Closure;
use DivisionByZeroError;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(int $i): ?string
	{
		return 'foo';
	}

	public function doImplicitArrayCreation(): void
	{
		$a['bla'] = 1;
		assertType('array{bla: 1}', $a);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doPlus($a, $b, int $c, int $d): void
	{
		assertType('int', $a + $b);
		assertNativeType('(array|float|int)', $a + $b);
		assertType('2', 1 + 1);
		assertNativeType('2', 1 + 1);
		assertType('int', $c + $d);
		assertNativeType('int', $c + $d);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doDiv($a, $b, int $c, int $d): void
	{
		assertType('(float|int)', $a / $b);
		assertNativeType('(float|int)', $a / $b);
		assertType('1', 1 / 1);
		assertNativeType('1', 1 / 1);
		assertType('(float|int)', $c / $d);
		assertNativeType('(float|int)', $c / $d);

		assertType('*ERROR*', $c / 0); // DivisionByZeroError
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doMod($a, $b, int $c, int $d): void
	{
		assertType('int', $a % $b);
		assertNativeType('int', $a % $b);
		assertType('0', 1 % 1);
		assertNativeType('0', 1 % 1);
		assertType('int', $c % $d);
		assertNativeType('int', $c % $d);

		assertType('*ERROR*', $c % 0); // DivisionByZeroError
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doMinus($a, $b, int $c, int $d): void
	{
		assertType('int', $a - $b);
		assertNativeType('(float|int)', $a - $b);
		assertType('0', 1 - 1);
		assertNativeType('0', 1 - 1);
		assertType('int', $c - $d);
		assertNativeType('int', $c - $d);
	}

	/**
	 * @param int $a
	 * @return void
	 */
	public function doBitwiseNot($a, int $b): void
	{
		assertType('int', ~$a);
		assertNativeType('int', ~$b);
		assertType('int', ~1);
		assertNativeType('int', ~1);
		assertType('int', ~$b);
		assertNativeType('int', ~$b);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doBitwiseAnd($a, $b, int $c, int $d): void
	{
		assertType('int', $a & $b);
		assertNativeType('int', $a & $b);
		assertType('1', 1 & 1);
		assertNativeType('1', 1 & 1);
		assertType('int', $c & $d);
		assertNativeType('int', $c & $d);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doBitwiseOr($a, $b, int $c, int $d): void
	{
		assertType('int', $a | $b);
		assertNativeType('int', $a | $b);
		assertType('1', 1 | 1);
		assertNativeType('1', 1 | 1);
		assertType('int', $c | $d);
		assertNativeType('int', $c | $d);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doBitwiseXor($a, $b, int $c, int $d): void
	{
		assertType('int', $a ^ $b);
		assertNativeType('int', $a ^ $b);
		assertType('0', 1 ^ 1);
		assertNativeType('0', 1 ^ 1);
		assertType('int', $c ^ $d);
		assertNativeType('int', $c ^ $d);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doMul($a, $b, int $c, int $d): void
	{
		assertType('int', $a * $b);
		assertNativeType('(float|int)', $a * $b);
		assertType('1', 1 * 1);
		assertNativeType('1', 1 * 1);
		assertType('int', $c * $d);
		assertNativeType('int', $c * $d);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doPow($a, $b, int $c, int $d): void
	{
		assertType('(float|int)', $a ** $b);
		assertNativeType('(float|int)', $a ** $b);
		assertType('1', 1 ** 1);
		assertNativeType('1', 1 ** 1);
		assertType('(float|int)', $c ** $d);
		assertNativeType('(float|int)', $c ** $d);
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return void
	 */
	public function doConcat($a, $b, string $c, string $d): void
	{
		assertType('string', $a . $b);
		assertNativeType('string', $a . $b);
		assertType("'1a'", '1' . 'a');
		assertNativeType("'1a'", '1' . 'a');
		assertType('string', $c . $d);
		assertNativeType('string', $c . $d);
	}

	function doUnaryPlus(int $i) {
		$a = '1';

		assertType('1', +$a);
		assertNativeType('1', +$a);
		assertType('int', +$i);
		assertNativeType('int', +$i);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doShiftLeft($a, $b, int $c, int $d): void
	{
		assertType('int', $a << $b);
		assertNativeType('(float|int)', $a << $b);
		assertType('8', 1 << 3);
		assertNativeType('8', 1 << 3);
		assertType('int', $c << $d);
		assertNativeType('int', $c << $d);
	}

	/**
	 * @param int $a
	 * @param int $b
	 * @return void
	 */
	public function doShiftRight($a, $b, int $c, int $d): void
	{
		assertType('int', $a >> $b);
		assertNativeType('(float|int)', $a >> $b);
		assertType('0', 1 >> 3);
		assertNativeType('0', 1 >> 3);
		assertType('int', $c >> $d);
		assertNativeType('int', $c >> $d);
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return void
	 */
	public function doSpaceship($a, $b, string $c, string $d): void
	{
		assertType('int<-1, 1>', $a <=> $b);
		assertNativeType('int<-1, 1>', $a <=> $b);
		assertType('-1', '1' <=> 'a');
		assertNativeType('-1', '1' <=> 'a');
		assertType('int<-1, 1>', $c <=> $d);
		assertNativeType('int<-1, 1>', $c <=> $d);
	}

	function doCast() {
		$a = '1';

		assertType('1', (int) $a);
		assertType("array{'1'}", (array) $a);
		assertType('stdClass', (object) $a);
		assertType('1.0', (double) $a);
		assertType('true', (bool) $a);
		assertType("'1'", (string) $a);
	}

}

function (): void {
	$foo = new Foo();
	assertType(Foo::class, $foo);
	assertType('string|null', $foo->doFoo(1));
	assertType($a = '1', (int) $a);
};

function (): void {
	assertType('array{foo: \'bar\'}', ['foo' => 'bar']);
	$a = [];
	assertType('array{}', $a);

};

function (): void {
	$a['bla'] = 1;
	assertType('array{bla: 1}', $a);
};

function (): void {
	$cb = fn () => 1;
	assertType('Closure(): 1', $cb);

	$cb = fn (string $s) => (int) $s;
	assertType('Closure(string): int', $cb);

	$cb = function () {
		return 1;
	};
	assertType('Closure(): 1', $cb);

	$a = 1;
	$cb = function () use (&$a) {
		return 1;
	};
	assertType('Closure(): 1', $cb);

	$cb = function (string $s) {
		return $s;
	};
	assertType('Closure(string): string', $cb);
};

function (): void {
	$a = 0;
	$cb = function () use (&$a): void {
		assertType('0|\'s\'', $a);
		$a = 's';
	};
	assertType('0|\'s\'', $a);
};

function (): void {
	$a = 0;
	$b = 0;
	$cb = function () use (&$a, $b): void {
		assertType('int<0, max>', $a);
		assertType('0', $b);
		$a = $a + 1;
		$b = 1;
	};
	assertType('int<0, max>', $a);
	assertType('0', $b);
};

function (): void {
	$a = 0;
	$cb = function () use (&$a): void {
		assertType('0|1', $a);
		$a = 1;
	};
	assertType('0|1', $a);
};

class FooWithStaticMethods
{

	public function doFoo(): void
	{
		assertType('GeneratorNodeScopeResolverTest\\FooWithStaticMethods', self::returnSelf());
		assertNativeType('GeneratorNodeScopeResolverTest\\FooWithStaticMethods', self::returnSelf());
		assertType('GeneratorNodeScopeResolverTest\\FooWithStaticMethods', self::returnPhpDocSelf());
		assertNativeType('mixed', self::returnPhpDocSelf());
	}

	public static function returnSelf(): self
	{

	}

	/**
	 * @return self
	 */
	public static function returnPhpDocSelf()
	{

	}

	/**
	 * @template T
	 * @param T $a
	 * @return T
	 */
	public static function genericStatic($a)
	{

	}

	public function doFoo2(): void
	{
		assertType('1', self::genericStatic(1));

		$s = 'GeneratorNodeScopeResolverTest\\FooWithStaticMethods';
		assertType('1', $s::genericStatic(1));
	}

	public function doIf(int $i): void {
		if ($i) {
			assertType('int<min, -1>|int<1, max>', $i);
		} else {
			assertType('0', $i);
		}

		assertType('int', $i);
	}

}

class ClosureFromCallableExtension
{

	/**
	 * @param callable(string, int=): bool $cb
	 */
	public function doFoo(callable $cb): void
	{
		assertType('callable(string, int=): bool', $cb);
		assertType('Closure(string, int=): bool', Closure::fromCallable($cb));
	}

}

/**
 * @template T
 */
class FooGeneric
{

	/**
	 * @param T $a
	 */
	public function __construct($a)
	{

	}

}

function (): void {
	$foo = new FooGeneric(5);
	assertType('GeneratorNodeScopeResolverTest\\FooGeneric<int>', $foo);
};

function (): void {
	$c = new /** @template T of int */ class(1, 2, 3) {
		/**
		 * @param T $i
		 */
		public function __construct(private int $i, private int $j, private int $k) {

		}
	};
};
