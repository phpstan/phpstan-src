<?php

namespace Levels\AcceptTypes;

class Foo
{

	/**
	 * @param int $i
	 * @param float $j
	 * @param float|string $k
	 * @param int|null $l
	 * @param int|float $m
	 */
	public function doFoo(
		int $i,
		float $j,
		$k,
		?int $l,
		$m
	)
	{
		$this->doBar($i);
		$this->doBar($j);
		$this->doBar($k);
		$this->doBar($l);
		$this->doBar($m);
	}

	public function doBar(int $i)
	{

	}

	/**
	 * @param float|string $a
	 * @param float|string|null $b
	 * @param int $c
	 * @param int|null $d
	 */
	public function doBaz(
		$a,
		$b,
		int $c,
		?int $d
	)
	{
		$this->doLorem($a);
		$this->doLorem($b);
		$this->doLorem($c);
		$this->doLorem($d);
		$this->doIpsum($a);
		$this->doIpsum($b);
		$this->doBar(null);
	}

	/**
	 * @param int|resource $a
	 */
	public function doLorem($a)
	{

	}

	/**
	 * @param float $a
	 */
	public function doIpsum($a)
	{

	}

	/**
	 * @param int[] $i
	 * @param float[] $j
	 * @param (float|string)[] $k
	 * @param (int|null)[] $l
	 * @param (int|float)[] $m
	 */
	public function doFooArray(
		array $i,
		array $j,
		array $k,
		array $l,
		array $m
	)
	{
		$this->doBarArray($i);
		$this->doBarArray($j);
		$this->doBarArray($k);
		$this->doBarArray($l);
		$this->doBarArray($m);
	}

	/**
	 * @param int[] $i
	 */
	public function doBarArray(array $i)
	{

	}

	public function doBazArray()
	{
		$ints = [1, 2, 3];
		$floats = [1.1, 2.2, 3.3];
		$floatsAndStrings = [1.1, 2.2];
		$intsAndNulls = [1, 2, 3];
		$intsAndFloats = [1, 2, 3];
		if (rand(0, 1) === 1) {
			$floatsAndStrings[] = 'str';
			$intsAndNulls[] = null;
			$intsAndFloats[] = 1.1;
		}

		$this->doBarArray($ints);
		$this->doBarArray($floats);
		$this->doBarArray($floatsAndStrings);
		$this->doBarArray($intsAndNulls);
		$this->doBarArray($intsAndFloats);
	}

	/**
	 * @param int|null $intOrNull
	 * @param int|float $intOrFloat
	 */
	public function doBazArrayUnionItemTypes(?int $intOrNull, $intOrFloat)
	{
		$intsAndNulls = [1, 2, 3, $intOrNull];
		$intsAndFloats = [1, 2, 3, $intOrFloat];
		$this->doBarArray($intsAndNulls);
		$this->doBarArray($intsAndFloats);
	}

	/**
	 * @param array<int, mixed> $array
	 */
	public function callableArray(
		array $array
	)
	{
		$this->expectCallable($array);
		$this->expectCallable('date');
		$this->expectCallable('nonexistentFunction');
		$this->expectCallable([$this, 'doFoo']);
	}

	public function expectCallable(callable $callable)
	{

	}

	/**
	 * @template T of FooCountableInterface
	 * @param iterable<mixed> $iterable
	 * @param mixed[] $array
	 * @param T $generic
	 * @param string $string
	 */
	public function iterableCountable(
		iterable $iterable,
		array $array,
		$generic,
		string $string
	)
	{
		echo count($iterable);
		echo count($array);
		echo count($generic);
		echo count($string);
	}

	/**
	 * @param string[] $strings
	 */
	public function benevolentUnionNotReported(array $strings)
	{
		foreach ($strings as $key => $val) {
			$this->doBar($key);
		}
	}

}

interface ParentFooInterface
{

}

interface FooInterface extends ParentFooInterface
{

}

interface FooCountableInterface extends \Countable
{

}

class FooImpl implements FooInterface
{

}

class ClosureAccepts
{

	public function doFoo(ParentFooInterface $parent)
	{
		$c = function (FooInterface $x, $y): FooInterface {
			return new FooImpl();
		};
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x): FooInterface { // less parameters - OK
			return new FooImpl();
		};
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x, $y, $z): FooInterface { // more parameters - error
			return new FooImpl();
		};

		$this->doBar($c);
		$this->doBaz($c);

		$c = function (ParentFooInterface $x): FooInterface { // parameter contravariance - OK
			return new FooImpl();
		};
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooImpl $x): FooInterface { // parameter covariance - error
			return new FooImpl();
		};
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x): FooImpl { // return type covariance - OK
			return new FooImpl();
		};
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x) use ($parent): ParentFooInterface { // return type contravariance - error
			return $parent;
		};
		$this->doBar($c);
		$this->doBaz($c);
	}

	public function doFooUnionClosures(FooInterface $foo, ParentFooInterface $parent)
	{
		$closure = function () use ($foo): FooInterface {
			return $foo;
		};
		$c = function (FooInterface $x, $y): FooInterface {
			return new FooImpl();
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x): FooInterface { // less parameters - OK
			return new FooImpl();
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x, $y, $z): FooInterface { // more parameters - error
			return new FooImpl();
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (ParentFooInterface $x): FooInterface { // parameter contravariance - OK
			return new FooImpl();
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooImpl $x): FooInterface { // parameter covariance - error
			return new FooImpl();
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x): FooImpl { // return type covariance - OK
			return new FooImpl();
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function (FooInterface $x) use ($parent): ParentFooInterface { // return type contravariance - error
			return $parent;
		};
		if (rand(0, 1) === 0) {
			$c = $closure;
		}
		$this->doBar($c);
		$this->doBaz($c);

		$c = function ($mixed) {
			return $mixed;
		};
		$this->doBar($c);
		$this->doBaz($c);
	}

	/**
	 * @param \Closure(FooInterface $x, int $y): FooInterface $closure
	 */
	public function doBar(
		\Closure $closure
	)
	{

	}

	/**
	 * @param callable(FooInterface $x, int $y): FooInterface $callable
	 */
	public function doBaz(
		callable $callable
	)
	{

	}

}

class Baz
{

	public function makeInt(): int
	{
		return 1;
	}

	public function makeFloat(): float
	{
		return 1.0;
	}

	/**
	 * @return float|string
	 */
	public function makeFloatOrString()
	{
		if (rand(0, 1) === 0) {
			return 1;
		} else {
			return 'foo';
		}
	}

	/**
	 * @return float|string|null
	 */
	public function makeFloatOrStringOrNull()
	{
		if (rand(0, 1) === 0) {
			return 1;
		} else {
			return 'foo';
		}
	}

	public function makeIntOrNull(): ?int
	{
		if (rand(0, 1) === 0) {
			return 1;
		} else {
			return null;
		}
	}

	/**
	 * @return int|float
	 */
	public function makeIntOrFloat()
	{
		if (rand(0, 1) === 0) {
			return 1;
		} else {
			return 1.0;
		}
	}

	public function doFoo()
	{
		$this->doBar($this->makeInt());
		$this->doBar($this->makeFloat());
		$this->doBar($this->makeFloatOrString());
		$this->doBar($this->makeIntOrNull());
		$this->doBar($this->makeIntOrFloat());
	}

	public function doBar(int $i)
	{

	}

	public function doBaz()
	{
		$this->doLorem($this->makeFloatOrString());
		$this->doLorem($this->makeFloatOrStringOrNull());
		$this->doLorem($this->makeInt());
		$this->doLorem($this->makeIntOrNull());
		$this->doIpsum($this->makeFloatOrString());
		$this->doIpsum($this->makeFloatOrStringOrNull());
		$this->doBar(null);
	}

	/**
	 * @param int|resource $a
	 */
	public function doLorem($a)
	{

	}

	/**
	 * @param float $a
	 */
	public function doIpsum($a)
	{

	}

	/**
	 * @return int[]
	 */
	public function makeIntArray(): array
	{
		return [1, 2, 3];
	}

	/**
	 * @return float[]
	 */
	public function makeFloatArray(): array
	{
		return [1.0, 2.0, 3.0];
	}

	/**
	 * @return (float|string)[]
	 */
	public function makeFloatOrStringArray(): array
	{
		return [1.0, 2.0, '3.0'];
	}

	/**
	 * @return (int|null)[]
	 */
	public function makeIntOrNullArray(): array
	{
		return [1, 2, null];
	}

	/**
	 * @return (int|float)[]
	 */
	public function makeIntOrFloatArray(): array
	{
		return [1.0, 2.0, 3];
	}

	public function doFooArray()
	{
		$this->doBarArray($this->makeIntArray());
		$this->doBarArray($this->makeFloatArray());
		$this->doBarArray($this->makeFloatOrStringArray());
		$this->doBarArray($this->makeIntOrNullArray());
		$this->doBarArray($this->makeIntOrFloatArray());
	}

	/**
	 * @param int[] $i
	 */
	public function doBarArray(array $i)
	{

	}

	public function makeFoo(): Foo
	{
		return new Foo();
	}

	/**
	 * @return Foo|mixed[]
	 */
	public function makeFooOrArray()
	{
		if (rand(0, 1) === 0) {
			return new Foo();
		}

		return [];
	}

	public function testUnions()
	{
		$a = [];
		if (rand(0, 1) === 0) {
			$a = $this->makeFoo();
		}

		$this->requireArray($a);
		$this->requireFoo($a);
	}

	public function testUnions2()
	{
		$a = [];
		if (rand(0, 1) === 0) {
			$a = $this->makeFooOrArray();
		}

		$this->requireArray($a);
		$this->requireFoo($a);
	}

	/**
	 * @param mixed[] $array
	 */
	private function requireArray(array $array)
	{

	}

	private function requireFoo(Foo $foo)
	{

	}

}

class ArrayShapes
{

	/**
	 * @param callable[] $callables
	 * @param string[] $strings
	 * @param int[] $integers
	 * @param iterable<callable> $iterable
	 */
	public function doFoo(
		array $callables,
		array $strings,
		array $integers,
		iterable $iterable
	)
	{
		$this->doBar($callables);
		$this->doBar($strings);
		$this->doBar($integers);
		$this->doBar([]);
		$this->doBar(['foo' => 'date']);
		$this->doBar(['foo' => 1]);
		$this->doBar(['foo' => 'date', 'bar' => 'date']);
		$this->doBar(['foo' => 'nonexistent']);
		$this->doBar(['bar' => 'date']);

		if (array_key_exists('foo', $callables)) {
			$this->doBar($callables);
		}

		$optional = [];
		if (rand(0, 1) === 0) {
			$optional['foo'] = 'date';
		}

		$this->doBar($optional);
		$this->doBar($iterable);
	}

	/**
	 * @param array{foo:callable} $one
	 */
	public function doBar(
		array $one
	)
	{

	}

}

class RequireClassString
{

	public function doFoo(string $s): void
	{
		$this->requireClassString($s);
		$this->requireGenericClassString($s);
	}

	/**
	 * @param class-string $s
	 */
	public function requireClassString(string $s): void
	{

	}

	/**
	 * @param class-string<\stdClass> $s
	 */
	public function requireGenericClassString(string $s): void
	{

	}

}

class RequireObjectWithoutClassType
{

	/**
	 * @param object $object
	 */
	public function doFoo($object): void
	{
		$this->requireStdClass($object);
		$this->requireStatic($object);
	}

	public function requireStdClass(\stdClass $stdClass): void
	{

	}

	/**
	 * @param static $static
	 */
	public function requireStatic($static): void
	{

	}

}

class RandomInt
{

	public function doThings(): int
	{
		return random_int(0, -1);
	}

	public function doInputMin(int $input): int
	{
		assert($input > 10);

		return random_int($input, 10);
	}

	public function doInputMax(int $input): int
	{
		assert($input < 340);

		return random_int(340, $input);
	}

	public function doStuff(): void
	{
		random_int(random_int(-1, 1), random_int(0, 1));
		random_int(random_int(-1, 0), random_int(-1, 1));
		random_int(random_int(-1, 1), random_int(-1, 1));
	}

}

class NumericStrings
{

	/**
	 * @param string $string
	 * @param numeric-string $numericString
	 */
	public function doFoo(string $string, string $numericString): void
	{
		$this->doBar('1');
		$this->doBar('foo');
		$this->doBar($string);
		$this->doBar($numericString);
	}

	/**
	 * @param numeric-string $numericString
	 */
	public function doBar(string $numericString): void
	{

	}
}

class AcceptNonEmpty
{

	/**
	 * @param array<mixed> $array
	 * @param non-empty-array<mixed> $nonEmpty
	 */
	public function doFoo(
		array $array,
		array $nonEmpty
	): void
	{
		$this->doBar([]);
		$this->doBar([1, 2, 3]);
		$this->doBar($array);
		$this->doBar($nonEmpty);
	}

	/**
	 * @param non-empty-array<mixed> $nonEmpty
	 */
	public function doBar(
		array $nonEmpty
	): void
	{

	}

}

class Implode {
	/**
	 * @param string|int|array $union
	 */
	public function partlySupportedUnion($union) {
		$imploded = implode('abc', $union);
	}

	/**
	 * @param int $invalid
	 */
	public function invalidType($invalid) {
		$imploded = implode('abc', $invalid);
	}
}

class Discussion8209
{
	public function test1(?int $id): int
	{
		return $id;
	}

	/**
	 * @return array<int>
	 */
	public function test2(?int $id): array
	{
		return [$id];
	}
}
