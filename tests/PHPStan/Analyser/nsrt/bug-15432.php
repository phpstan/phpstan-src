<?php // lint >= 8.1

namespace Bug15432;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * @template T of string
 * @param array<T, callable(): void> $callbacks
 */
function consume(array $callbacks): void
{
	foreach ($callbacks as $handler) {
		$handler();
	}
}

consume([
	'method' => (new class {
		public function handle(): void {}
	})->handle(...),
	'closure' => function (): void {},
]);

/**
 * @template T
 * @param array{producer: callable(): T, consumer: callable(T): void} $pair
 */
function pipe(array $pair): void {}

function expressionCallables(Closure $f, bool $b): void
{
	pipe(['producer' => $f(...), 'consumer' => function ($v): void {
		assertType('mixed', $v);
	}]);
	pipe(['producer' => 'strlen'(...), 'consumer' => function ($v): void {
		assertType('int<0, max>', $v);
	}]);
	pipe(['producer' => $b ? $f(...) : $f(...), 'consumer' => function ($v): void {
		assertType('mixed', $v);
	}]);
}

class Producer
{

	public function produce(): int
	{
		return 1;
	}

	public static function produceStatic(): int
	{
		return 1;
	}

}

function methodCallables(Producer $p, string $name, string $className): void
{
	pipe(['producer' => $p->produce(...), 'consumer' => function ($v): void {
		assertType('int', $v);
	}]);

	$callable = $p->produce(...);
	pipe(['producer' => $callable, 'consumer' => function ($v): void {
		assertType('int', $v);
	}]);

	pipe(['producer' => Producer::produceStatic(...), 'consumer' => function ($v): void {
		assertType('int', $v);
	}]);

	pipe(['producer' => (new class {
		public function produce(): string
		{
			return 'a';
		}
	})->produce(...), 'consumer' => function ($v): void {
		assertType('string', $v);
	}]);

	pipe(['producer' => $p->{$name}(...), 'consumer' => function ($v): void {
		assertType('mixed', $v);
	}]);

	pipe(['producer' => $className::{$name}(...), 'consumer' => function ($v): void {
		assertType('mixed', $v);
	}]);
}

/**
 * @template T
 * @param array{first: mixed, value: T, callback: callable(T): void} $spec
 */
function run(array $spec): void
{
	($spec['callback'])($spec['value']);
}

function mutate(int &$value): void
{
	$value++;
}

/** @phpstan-pure */
function pureLength(string $s): int
{
	return strlen($s);
}

function effectsOfEarlierItems(): void
{
	$i = 0;
	run([
		'first' => $i++,
		'value' => $i,
		'callback' => function ($v): void {
			assertType('1', $v);
		},
	]);

	$x = 1;
	run([
		'first' => $x = 'str',
		'value' => $x,
		'callback' => function ($v): void {
			assertType("'str'", $v);
		},
	]);

	$n = 0;
	run([
		'first' => [$n++],
		'value' => $n,
		'callback' => function ($v): void {
			assertType('1', $v);
		},
	]);

	$r = 0;
	run([
		'first' => mutate($r),
		'value' => $r,
		'callback' => function ($v): void {
			assertType('int', $v);
		},
	]);

	$k = 0;
	run([
		($k++ === 0 ? 'first' : 'first') => $k,
		'value' => $k,
		'callback' => function ($v): void {
			assertType('1', $v);
		},
	]);

	$l = 0;
	run([
		'first' => $l++,
		'value' => 42,
		'callback' => function ($v): void {
			assertType('42', $v);
		},
	]);
}

function earlierItemsWithoutEffects(): void
{
	$j = 0;
	run([
		'value' => $j,
		'first' => $j++,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);

	$k = 0;
	run([
		'first' => strlen('abc'),
		'value' => $k,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);

	$m = 0;
	run([
		'first' => pureLength('abc'),
		'value' => $m,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);
}

class HasProp
{

	public int $prop = 0;

	public function reset(): void
	{
		$this->prop = 7;
	}

	/** @phpstan-pure */
	public function pureGetter(): int
	{
		return 1;
	}

	public function impureMethod(): void
	{
		$this->prop = 5;
		run([
			'first' => $this->reset(),
			'value' => $this->prop,
			'callback' => function ($v): void {
				assertType('int', $v);
			},
		]);
	}

	public function pureMethod(): void
	{
		$this->prop = 5;
		run([
			'first' => $this->pureGetter(),
			'value' => $this->prop,
			'callback' => function ($v): void {
				assertType('5', $v);
			},
		]);
	}

	/**
	 * @return \Generator<int, int, mixed, void>
	 */
	public function yieldBefore(): \Generator
	{
		$this->prop = 5;
		// as anywhere else, yield keeps the narrowed property
		run([
			'first' => yield 1,
			'value' => $this->prop,
			'callback' => function ($v): void {
				assertType('5', $v);
			},
		]);
	}

}

/**
 * @template K of int
 * @param array<K, array{int, callable(K): void}> $map
 */
function keyed(array $map): void {}

function plainVariableKeyIsReadAfterItsValue(): void
{
	$j = 0;
	keyed([$j => [$j++, function ($key): void {
		assertType('1', $key);
	}]]);
}

/**
 * @template T
 * @param array{first: mixed, value: T, callback: callable(T): void, after: mixed} $spec
 */
function runAfter(array $spec): void
{
	($spec['callback'])($spec['value']);
}

function closuresCaptureWhereTheyAreCreated(): void
{
	$i = 0;
	runAfter([
		'first' => 1,
		'value' => 1,
		'callback' => function ($v) use ($i): void {
			assertType('1', $v);
			assertType('0', $i);
		},
		'after' => $i++,
	]);

	$j = 0;
	runAfter([
		'first' => 1,
		'value' => 'a',
		'callback' => function ($v) use ($j): void {
			assertType("'a'", $v);
			assertType('0', $j);
		},
		'after' => $j++,
	]);
}

function closureBeforeTheValue(string $s): void
{
	runAfter([
		'callback' => function ($v): void {
			assertType('string', $v);
		},
		'first' => 1,
		'value' => $s,
		'after' => 1,
	]);
}

function byReferenceUses(): void
{
	$i = 0;
	run([
		'first' => function () use (&$i): void {},
		'value' => $i,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);

	$j = 0;
	run([
		'first' => function () use (&$j): void {
			$j = 'a';
		},
		'value' => $j,
		'callback' => function ($v): void {
			assertType("0|'a'", $v);
		},
	]);
	assertType("0|'a'", $j);

	$l = 0;
	run([
		'first' => function () use (&$l): void {
			$l++;
		},
		'value' => $l,
		'callback' => function ($v): void {
			// the type of the walked item itself, as T resolves for the call
			assertType('int<0, max>', $v);
		},
	]);

	$k = 0;
	run([
		ByRefKeys::FIRST => function () use (&$k): void {},
		'value' => 'x',
		'callback' => function ($v): void {
			assertType("'x'", $v);
		},
	]);
}

final class ByRefKeys
{

	public const FIRST = 'first';

}

/**
 * @template T
 * @param array{nested: array{callback: callable(T): void}, value: T} $spec
 */
function nested(array $spec): void
{
	($spec['nested']['callback'])($spec['value']);
}

function nestedLiterals(string $s): void
{
	nested([
		'nested' => ['callback' => function ($v): void {
			assertType('string', $v);
		}],
		'value' => $s,
	]);

	$i = 0;
	nested([
		'nested' => ['callback' => function ($v) use (&$i): void {
			assertType("'x'", $v);
		}],
		'value' => 'x',
	]);
}
