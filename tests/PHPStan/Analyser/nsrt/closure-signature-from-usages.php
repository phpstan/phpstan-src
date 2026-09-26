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

	public function userExample(): void
	{
		$c = function ($a) {
			assertType('1|2|string', $a);
			assertNativeType('mixed', $a);
		};
		$c(1);
		$c(2);

		doFoo($c);
		assertType('Closure(1|2|string): void', $c);
	}

	public function arrowFunction(): void
	{
		$f = fn ($a) => $a;
		$f(1);
		assertType("1|'x'", $f('x'));
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
	}

	/**
	 * @return Closure(int): void
	 */
	public function returnedClosure(): Closure
	{
		$c = function ($a): void {
			assertType('int', $a);
		};

		return $c;
	}

	public function typedProperty(): void
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		$this->typedHandler = $c;
	}

	public function varTag(): void
	{
		/** @var callable(int): void $c */
		$c = function ($a): void {
			assertType('int', $a);
		};
	}

	public function escapeToUntypedProperty(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$this->untypedHandler = $c;
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
	}

	public function escapeViaGetDefinedVars(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$vars = get_defined_vars();
		takesMixed($vars);
	}

	public function escapeViaPropertyOffset(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$this->untypedHandler['x'] = $c;
	}

	public function escapeViaGlobal(): void
	{
		global $globalHandler;
		$globalHandler = function ($a) {
			assertType('mixed', $a);
		};
		$globalHandler(1);
	}

	public function escapeViaGlobalsArray(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$GLOBALS['handler'] = $c;
	}

	public function escapeViaByRefParameter(&$out): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		$out = $c;
	}

	public function escapeViaYield(): \Generator
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		yield $c;
	}

	public function escapeViaInclude(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
		include __DIR__ . '/closure-signature-from-usages-included.php';
	}

	public function escapeViaDynamicVariable(string $name): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
		$c(1);
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
		};
		$g();
		takesBareCallable($out);
	}

	public function neverUsed(): void
	{
		$c = function ($a) {
			assertType('mixed', $a);
		};
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
	}

	public function defaultAndVariadic(): void
	{
		$c = function ($a = null) {
			assertType('1|null', $a);
		};
		$c(1);

		$d = function (...$xs) {
			assertType('array<int<0, max>|string, 1|2>', $xs);
		};
		$d(1, 2);
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
	}

	public function reassignment(): void
	{
		$c = function ($a) {
			assertType('1', $a);
		};
		$c(1);
		$c = function ($b) {
			assertType("'x'", $b);
		};
		$c('x');
	}

	public function arrayOfCallbacks(): void
	{
		$c = function ($a): void {
			assertType('int', $a);
		};
		takesCallbacks([$c]);
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

}
