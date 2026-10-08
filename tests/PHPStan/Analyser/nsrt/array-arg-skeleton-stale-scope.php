<?php // lint >= 8.1

namespace ArrayArgSkeletonStaleScope;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param array{first: mixed, value: T, callback: callable(T): void} $spec
 */
function run(array $spec): void
{
}

/**
 * @template T
 * @param array{0: array<mixed, T>, 1: callable(T): void} $spec
 */
function runValues(array $spec): void
{
}

/**
 * @template K of int
 * @param array{0: array<K, mixed>, 1: callable(K): void} $spec
 */
function runKeys(array $spec): void
{
}

class Foo
{

	public ?int $prop = null;

	public function reset(): void
	{
		$this->prop = null;
	}

	public function impureCall(): void
	{
		$this->prop = 5;
		run([
			'first' => $this->reset(),
			'value' => $this->prop,
			'callback' => function ($v): void {
				assertType('mixed', $v);
			},
		]);
	}

}

function assign(): void
{
	$x = 1;
	run([
		'first' => $x = 'str',
		'value' => $x,
		'callback' => function ($v): void {
			assertType('mixed', $v);
		},
	]);
}

function increment(): void
{
	$i = 0;
	run([
		'first' => $i++,
		'value' => $i,
		'callback' => function ($v): void {
			assertType('mixed', $v);
		},
	]);
}

function nested(): void
{
	$i = 0;
	run([
		'first' => [$i++],
		'value' => $i,
		'callback' => function ($v): void {
			assertType('mixed', $v);
		},
	]);
}

function valueReadAfterKey(): void
{
	$i = 0;
	runValues([
		[$i++ => $i],
		function ($v): void {
			assertType('mixed', $v);
		},
	]);
}

function keyReadAfterValue(): void
{
	$i = 0;
	runKeys([
		[$i => $i++],
		function ($k): void {
			assertType('int', $k);
		},
	]);
}

function neutralSiblings(int $x): void
{
	$i = 0;
	run([
		'first' => $x,
		'value' => $i,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);
	run([
		'first' => function () use ($i): void {
		},
		'value' => $i,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);
	run([
		'first' => strlen(...),
		'value' => $i,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);
	run([
		'first' => \PHP_VERSION_ID,
		'value' => $i,
		'callback' => function ($v): void {
			assertType('0', $v);
		},
	]);
}
