<?php // lint >= 8.0

namespace ClosureSignatureFromUsages;

use Closure;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/**
 * @param callable(string): void $cb
 */
function doFoo(callable $cb): void
{
}

/**
 * @param callable(non-empty-array<int>): void $cb
 */
function takesNonEmptyArrayCallback(callable $cb): void
{
}

/**
 * @param callable(): (callable(string): void) $cb
 */
function takesFactory(callable $cb): void
{
}

/**
 * @param array<callable(int): void> $cbs
 */
function takesCallbacks(array $cbs): void
{
}

function takesMixed(mixed $m): void
{
}

class InvokedCountry
{

	public static function tryFrom(mixed $value): ?self
	{
		return null;
	}

}

class InvokedRegion
{

	public static function tryFrom(mixed $value): ?self
	{
		return null;
	}

}

/**
 * @template T of \BackedEnum|int|string
 * @param \Closure(T): string $cb
 * @return list<T>
 */
function takesConvertor(\Closure $cb): array
{
	return [];
}

function takesBareCallable(callable $cb): void
{
}

function takesBareClosure(Closure $cb): void
{
}

/**
 * @template T
 */
class Collection
{

	/**
	 * @template U
	 * @param callable(T): U $cb
	 * @return self<U>
	 */
	public function map(callable $cb): self
	{
		return $this;
	}

}

class Foo
{

	/** @var Closure(int): void */
	private Closure $typedHandler;

	/** @var mixed */
	private $untypedHandler;

	/** @var Closure(int): mixed */
	private Closure $typedArrowHandler;

	public function userExample(): void
	{
		$c = function ($a) {
			assertType('1|2|string', $a);
			assertNativeType('mixed', $a);
		};
		assertType('Closure(1|2|string): void', $c);
		$c(1);
		$c(2);

		doFoo($c);
		assertType('Closure(1|2|string): void', $c);

		$f = fn ($a) => assertType('1|2|string', $a);
		assertType('Closure(1|2|string): mixed', $f);
		$f(1);
		$f(2);

		doFoo($f);
		assertType('Closure(1|2|string): mixed', $f);
	}

	public function arrowFunction(): void
	{
		$f = fn ($a) => $a;
		$f(1);
		assertType("'x'", $f('x'));
		assertType("Closure(1|'x'): (1|'x')", $f);

		$c = function ($a) {
			assertType("1|'x'", $a);
			return $a;
		};
		$c(1);
		assertType("'x'", $c('x'));
		assertType("Closure(1|'x'): (1|'x')", $c);
	}

	public function arrayOffsets(): void
	{
		$h = [];
		$h['a'] = function ($x) {
			assertType('5', $x);
		};
		$h['a'](5);

		$literal = [
			'b' => function ($y) {
				assertType("'foo'", $y);
			},
		];
		$literal['b']('foo');
		assertType('array{a: Closure(5): void}', $h);
		assertType("array{b: Closure('foo'): void}", $literal);

		$arrowH = [];
		$arrowH['a'] = fn ($x) => assertType('6', $x);
		$arrowH['a'](6);

		$arrowLiteral = [
			'b' => fn ($y) => assertType("'bar'", $y),
		];
		$arrowLiteral['b']('bar');
		assertType('array{a: Closure(6): mixed}', $arrowH);
		assertType("array{b: Closure('bar'): mixed}", $arrowLiteral);
	}

	public function listOfClosures(): void
	{
		$hs = [
			function ($x) {
				assertType("'s'", $x);
			},
			fn ($y) => assertType("'s'", $y),
		];
		foreach ($hs as $h) {
			$h('s');
		}
		assertType("array{Closure('s'): void, Closure('s'): mixed}", $hs);
	}

	public function aliasAndUnion(bool $b): void
	{
		$c = function ($a) {
			assertType('1', $a);
		};
		$d = $c;
		$d(1);

		$e = $b ? function ($p) {
			assertType('2', $p);
		} : function ($q) {
			assertType('2', $q);
		};
		$e(2);
		assertType('Closure(1): void', $c);
		assertType('Closure(1): void', $d);
		assertType('Closure(2): void', $e);

		$f = fn ($a) => assertType('3', $a);
		$g = $f;
		$g(3);
		assertType('Closure(3): mixed', $f);
		assertType('Closure(3): mixed', $g);

		$h = $b ? fn ($p) => assertType('4', $p) : fn ($q) => assertType('4', $q);
		$h(4);
		assertType('Closure(4): mixed', $h);
	}

	/**
	 * @param list<int> $ints
	 * @param array<string> $strings
	 * @param Collection<int> $collection
	 */
	public function genericTargets(array $ints, array $strings, Collection $collection): void
	{
		$cmp = function ($a, $b) {
			assertType('int', $a);
			assertType('int', $b);
			return $a <=> $b;
		};
		usort($ints, $cmp);

		$mapper = function ($i) {
			assertType('1|2', $i);
			return $i;
		};
		array_map($mapper, [1, 2]);

		$filter = function ($s) {
			assertType('string', $s);
			return $s !== '';
		};
		array_filter($strings, $filter);

		$collectionMapper = function ($value) {
			assertType('int', $value);
			return (string) $value;
		};
		assertType('ClosureSignatureFromUsages\Collection<string>', $collection->map($collectionMapper));

		assertType('Closure(int, int): int<-1, 1>', $cmp);
		assertType('Closure(1|2): (1|2)', $mapper);
		assertType('Closure(string): bool', $filter);
		assertType('Closure(int): decimal-int-string', $collectionMapper);

		$arrowCmp = fn ($a, $b) => $a <=> $b;
		usort($ints, $arrowCmp);
		$arrowMapper = fn ($i) => $i;
		array_map($arrowMapper, [1, 2]);
		$arrowFilter = fn ($s) => $s !== '';
		array_filter($strings, $arrowFilter);
		$arrowCollectionMapper = fn ($value) => (string) $value;
		assertType('ClosureSignatureFromUsages\Collection<string>', $collection->map($arrowCollectionMapper));

		assertType('Closure(int, int): int<-1, 1>', $arrowCmp);
		assertType('Closure(1|2): (1|2)', $arrowMapper);
		assertType('Closure(string): bool', $arrowFilter);
		assertType('Closure(int): decimal-int-string', $arrowCollectionMapper);
	}

	/**
	 * @return Closure(int): void
	 */
	public function returnedClosure(): Closure
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		assertType('Closure(int): void', $c);

		return $c;
	}

	/**
	 * @return Closure(int): string
	 */
	public function returnedArrowFunction(): Closure
	{
		$f = fn ($a) => (string) $a;
		assertType('Closure(int): decimal-int-string', $f);

		return $f;
	}

	public function typedProperty(): void
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		$this->typedHandler = $c;
		assertType('Closure(int): void', $c);

		$f = fn ($a) => assertType('int', $a);
		$this->typedArrowHandler = $f;
		assertType('Closure(int): mixed', $f);
	}

	public function varTag(): void
	{
		/** @var callable(int): void $c */
		$c = function ($a): void {
			assertType('int', $a);
		};
		assertType('callable(int): void', $c);

		/** @var callable(int): mixed $f */
		$f = fn ($a) => assertType('int', $a);
		assertType('callable(int): mixed', $f);
	}

	public function escapeToUntypedProperty(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$this->untypedHandler = $c;
		assertType('Closure(mixed): void', $c);

		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		$this->untypedHandler = $f;
		assertType('Closure(mixed): mixed', $f);
	}

	public function escapeToMixedAndBareCallables(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		takesMixed($c);

		$d = function ($a) {
			assertType('mixed', $a);
		};
		$d(1);
		takesBareCallable($d);

		$e = function ($a) {
			assertType('mixed', $a);
		};
		$e(1);
		takesBareClosure($e);

		$f = function ($a) {
			assertType('mixed', $a);
		};
		call_user_func($f, 1);

		assertType('Closure(mixed): void', $c);
		assertType('Closure(mixed): void', $d);
		assertType('Closure(mixed): void', $e);
		assertType('Closure(mixed): void', $f);

		$arrowC = fn ($a) => assertType('mixed', $a);
		$arrowC(1);
		takesMixed($arrowC);

		$arrowD = fn ($a) => assertType('mixed', $a);
		$arrowD(1);
		takesBareCallable($arrowD);

		$arrowE = fn ($a) => assertType('mixed', $a);
		$arrowE(1);
		takesBareClosure($arrowE);

		$arrowF = fn ($a) => assertType('mixed', $a);
		call_user_func($arrowF, 1);

		assertType('Closure(mixed): mixed', $arrowC);
		assertType('Closure(mixed): mixed', $arrowD);
		assertType('Closure(mixed): mixed', $arrowE);
		assertType('Closure(mixed): mixed', $arrowF);
	}

	public function escapeViaGetDefinedVars(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		$vars = get_defined_vars();
		takesMixed($vars);
		assertType('Closure(mixed): void', $c);
		assertType('Closure(mixed): mixed', $f);
	}

	public function escapeViaPropertyOffset(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$this->untypedHandler['x'] = $c;
		assertType('Closure(mixed): void', $c);

		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		$this->untypedHandler['y'] = $f;
		assertType('Closure(mixed): mixed', $f);
	}

	public function escapeViaGlobal(): void
	{
		global $globalHandler;
		$globalHandler = function ($a) {
			assertType('mixed', $a);
		};
		$globalHandler(1);
		assertType('Closure(mixed): void', $globalHandler);

		global $globalArrowHandler;
		$globalArrowHandler = fn ($a) => assertType('mixed', $a);
		$globalArrowHandler(1);
		assertType('Closure(mixed): mixed', $globalArrowHandler);
	}

	public function escapeViaGlobalsArray(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$GLOBALS['handler'] = $c;
		assertType('Closure(mixed): void', $c);

		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		$GLOBALS['arrowHandler'] = $f;
		assertType('Closure(mixed): mixed', $f);
	}

	public function escapeViaByRefParameter(&$out, &$arrowOut): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$out = $c;
		assertType('Closure(mixed): void', $c);

		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		$arrowOut = $f;
		assertType('Closure(mixed): mixed', $f);
	}

	public function escapeViaYield(): \Generator
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		yield $c;
		assertType('Closure(mixed): void', $c);

		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		yield $f;
		assertType('Closure(mixed): mixed', $f);
	}

	/** @return \Generator<int, Closure(int): mixed> */
	public function yieldToTypedGenerator(): \Generator
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		yield $c;
		assertType('Closure(int): void', $c);

		$f = fn ($a) => assertType('int', $a);
		yield $f;
		assertType('Closure(int): mixed', $f);
	}

	/** @return \Generator<callable(string): void, int> */
	public function yieldKeyToTypedGenerator(): \Generator
	{
		$c = function ($a): void {
			assertType('string', $a);
		};
		yield $c => 1;
		assertType('Closure(string): void', $c);
	}

	/** @return \Generator<int, callable(int): void> */
	public function yieldFromToTypedGenerator(): \Generator
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		yield from [$c];
		assertType('Closure(int): void', $c);
	}

	/** @return iterable<int, callable(int): mixed> */
	public function yieldToTypedIterable(): iterable
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		yield $c;
		assertType('Closure(int): void', $c);

		$f = fn ($a) => assertType('int', $a);
		yield $f;
		assertType('Closure(int): mixed', $f);
	}

	public function escapeViaInclude(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$f = fn ($a) => assertType('mixed', $a);
		$f(1);
		include __DIR__ . '/closure-signature-from-usages-included.php';
		assertType('Closure(mixed): void', $c);
		assertType('Closure(mixed): mixed', $f);
	}

	public function escapeViaDynamicVariable(string $name): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$f = fn ($a) => assertType('mixed', $a);
		assertType('Closure(mixed): void', $c);
		assertType('Closure(mixed): mixed', $f);
		$f(1);
		$$name = 'foo';
	}

	public function escapeViaByRefUse(): void
	{
		$out = null;
		$g = function () use (&$out) {
			$c = function ($a) {
				assertType('mixed', $a);
			};
			$c(1);
			$out = $c;
			assertType('Closure(mixed): void', $c);
		};
		$g();
		takesBareCallable($out);

		$arrowOut = null;
		$h = function () use (&$arrowOut) {
			$f = fn ($a) => assertType('mixed', $a);
			$f(1);
			$arrowOut = $f;
			assertType('Closure(mixed): mixed', $f);
		};
		$h();
		takesBareCallable($arrowOut);
	}

	public function neverUsed(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		assertType('Closure(mixed): void', $c);

		$f = fn ($a) => assertType('mixed', $a);
		assertType('Closure(mixed): mixed', $f);
	}

	public function typedParameters(): void
	{
		$c = function (array $a) {
			assertType('array{1}', $a);
			assertNativeType('array', $a);
		};
		$c([1]);

		$d = function (array $a) {
			assertType('non-empty-array<int>', $a);
		};
		takesNonEmptyArrayCallback($d);

		$e = function (int $a, $b) {
			assertType('5', $a);
			assertType("'x'", $b);
		};
		$e(5, 'x');

		$g = function (array $a) {
			assertType('array', $a);
		};
		$g([1]);
		takesMixed($g);

		assertType('Closure(array{1}): void', $c);
		assertType('Closure(non-empty-array<int>): void', $d);
		assertType("Closure(5, 'x'): void", $e);
		assertType('Closure(array): void', $g);

		$arrowC = fn (array $a) => assertType('array{2}', $a);
		$arrowC([2]);

		$arrowD = fn (array $a) => assertType('non-empty-array<int>', $a);
		takesNonEmptyArrayCallback($arrowD);

		$arrowE = fn (int $a, $b) => [assertType('6', $a), assertType("'y'", $b)];
		$arrowE(6, 'y');

		$arrowG = fn (array $a) => assertType('array', $a);
		$arrowG([2]);
		takesMixed($arrowG);

		assertType('Closure(array{2}): mixed', $arrowC);
		assertType('Closure(non-empty-array<int>): mixed', $arrowD);
		assertType("Closure(6, 'y'): array{mixed, mixed}", $arrowE);
		assertType('Closure(array): mixed', $arrowG);
	}

	public function defaultAndVariadic(): void
	{
		$c = function ($a = null) {
			assertType('1|null', $a);
		};
		$c(1);

		$d = function (...$xs) {
			assertType('array<int<0, max>|string, mixed>', $xs);
		};
		$d(1, 2);
		assertType('Closure(1|null=): void', $c);
		assertType('Closure(mixed ...): void', $d);

		$f = fn ($a = null) => $a;
		assertType('1', $f(1));
		assertType('Closure(1|null=): (1|null)', $f);

		$g = fn (...$xs) => assertType('array<int<0, max>|string, mixed>', $xs);
		$g(1, 2);
		assertType('Closure(mixed ...): mixed', $g);
	}

	public function nestedUse(): void
	{
		$c = function ($a) {
			assertType("1|'x'", $a);
		};
		$g = function () use ($c) {
			$c(1);
		};
		$c('x');
		assertType("Closure(1|'x'): void", $c);

		$f = fn ($a) => assertType("2|'y'", $a);
		$h = fn () => $f(2);
		$f('y');
		assertType("Closure(2|'y'): mixed", $f);
		assertType('Closure(): mixed', $h);

		$i = fn ($a) => $a;
		$j = function () use ($i) {
			return $i(3);
		};
		$i('z');
		assertType("Closure(3|'z'): (3|'z')", $i);
		assertType('Closure(): 3', $j);
		assertType('3', $j());
	}

	public function recursion(): void
	{
		$fact = function ($n) use (&$fact) {
			if ($n <= 1) {
				return 1;
			}

			return $n * $fact($n - 1);
		};
		$fact(5);
		assertType('Closure(float|int): (float|int)', $fact);
	}

	public function recursionThroughByRefUse(\stdClass $o): void
	{
		$check = function (\stdClass $o, bool $first) use (&$check): void {
			assertType('bool', $first);
			if (rand(0, 1)) {
				$check($o, false);
			}
		};
		$check($o, true);
		assertType('Closure(stdClass, bool): void', $check);
	}

	public function reassignment(): void
	{
		$c = function ($a) {
			assertType('1', $a);
		};
		$c(1);
		assertType('Closure(1): void', $c);
		$c = function ($b) {
			assertType("'x'", $b);
		};
		$c('x');
		assertType("Closure('x'): void", $c);

		$f = fn ($a) => assertType('2', $a);
		$f(2);
		assertType('Closure(2): mixed', $f);
		$f = fn ($b) => assertType("'y'", $b);
		$f('y');
		assertType("Closure('y'): mixed", $f);
	}

	public function arrayOfCallbacks(): void
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		takesCallbacks([$c]);
		assertType('Closure(int): void', $c);

		$f = fn ($a) => assertType('int', $a);
		takesCallbacks([$f]);
		assertType('Closure(int): mixed', $f);
	}

	public function returnContextualTypingStored(): void
	{
		$c = function () {
			return function ($x): void {
				assertType('string', $x);
			};
		};
		takesFactory($c);
	}

	public function returnContextualTypingDirect(): void
	{
		takesFactory(function () {
			return function ($x): void {
				assertType('string', $x);
			};
		});
	}

	public function returnContextualTypingArrow(): void
	{
		takesFactory(fn () => function ($x): void {
			assertType('string', $x);
		});

		$c = fn () => fn ($y) => assertType('string', $y);
		takesFactory($c);
	}

	public function settledClosureSentToGenericTarget(): void
	{
		$convertor = static function (int|string $value): string {
			assertType('int|string', $value);
			return 'x' . $value;
		};
		assertType('list<int|string>', takesConvertor($convertor));
		assertType('static-Closure(int|string): non-falsy-string', $convertor);

		$arrowConvertor = static fn (int|string $value): string => 'x' . $value;
		assertType('list<int|string>', takesConvertor($arrowConvertor));
		assertType('static-Closure(int|string): non-falsy-string', $arrowConvertor);
	}

	public function variadicParameterKeepsDeclaredType(): void
	{
		$inner = static fn (string $a, float $b): array => [$a, $b];
		$outer = static function (mixed ...$args) use ($inner): array {
			assertType('array<int<0, max>|string, mixed>', $args);
			return $inner(...$args);
		};
		$outer('x', 1.0);
		assertType('static-Closure(string, float): array{string, float}', $inner);
		assertType('static-Closure(mixed ...): array{string, float}', $outer);

		$arrowOuter = static fn (mixed ...$args): array => $inner(...$args);
		$arrowOuter('x', 1.0);
		assertType('static-Closure(mixed ...): array{string, float}', $arrowOuter);
	}

	public function invocationReturnsWhatTheBodyReturnsForItsArguments(mixed $a, mixed $b): void
	{
		$toEnumList = static function (mixed $value, string $enumClassName): array {
			assertType("'ClosureSignatureFromUsages\\\\InvokedCountry'|'ClosureSignatureFromUsages\\\\InvokedRegion'", $enumClassName);
			$enumValues = [];
			foreach ((array) $value as $val) {
				$enumValue = $enumClassName::tryFrom($val);
				if ($enumValue !== null) {
					$enumValues[] = $enumValue;
				}
			}
			return $enumValues;
		};
		assertType('list<ClosureSignatureFromUsages\\InvokedCountry>', $toEnumList($a, InvokedCountry::class));
		assertType('list<ClosureSignatureFromUsages\\InvokedRegion>', $toEnumList($b, InvokedRegion::class));

		$inner = static function (mixed $value) use ($toEnumList): void {
			assertType('array{}|array{ClosureSignatureFromUsages\\InvokedCountry}', $toEnumList($value, InvokedCountry::class));
		};
		$inner(1);
		assertType("static-Closure(mixed, 'ClosureSignatureFromUsages\\\\InvokedCountry'|'ClosureSignatureFromUsages\\\\InvokedRegion'): list<ClosureSignatureFromUsages\\InvokedCountry|ClosureSignatureFromUsages\\InvokedRegion>", $toEnumList);
		assertType('static-Closure(1): void', $inner);

		$arrowToEnumList = static fn (mixed $value, string $enumClassName): ?object => $enumClassName::tryFrom($value);
		assertType('ClosureSignatureFromUsages\\InvokedCountry|null', $arrowToEnumList($a, InvokedCountry::class));
		assertType('ClosureSignatureFromUsages\\InvokedRegion|null', $arrowToEnumList($b, InvokedRegion::class));
		assertType("static-Closure(mixed, 'ClosureSignatureFromUsages\\\\InvokedCountry'|'ClosureSignatureFromUsages\\\\InvokedRegion'): (ClosureSignatureFromUsages\\InvokedCountry|ClosureSignatureFromUsages\\InvokedRegion|null)", $arrowToEnumList);
	}

	public function invocationOfAReassignedVariable(): void
	{
		$f = static fn (int|string $x): int|string => $x;
		assertType('1', $f(1));
		$f = static fn (int|string $x): string => 'other';
		assertType("'other'", $f(1));
		$f('a');
		assertType("static-Closure(1|'a'): 'other'", $f);

		$c = static function (int|string $x): int|string {
			return $x;
		};
		assertType('1', $c(1));
		assertType('static-Closure(1): 1', $c);
		$c = static function (int|string $x): string {
			return 'other';
		};
		assertType("'other'", $c(1));
		$c('a');
		assertType("static-Closure(1|'a'): 'other'", $c);
	}

	/**
	 * @param list<int> $args
	 */
	public function unpackedArguments(array $args): void
	{
		$c = function ($a, $b = null) {
			assertType("'x'|int", $a);
			assertType('int|null', $b);
		};
		$c('x');
		$c(...$args);
		assertType("Closure('x'|int, int|null=): void", $c);

		$d = function ($a) {
			assertType("5|'y'", $a);
		};
		$d(...[5]);
		$d(...['a' => 'y']);
		assertType("Closure(5|'y'): void", $d);

		$f = fn ($a, $b = null) => [assertType("'x'|int", $a), assertType('int|null', $b)];
		$f('x');
		$f(...$args);
		assertType("Closure('x'|int, int|null=): array{mixed, mixed}", $f);
	}

}
