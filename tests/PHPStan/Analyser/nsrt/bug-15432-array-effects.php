<?php // lint >= 8.1

namespace Bug15432ArrayEffects;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param array{first: mixed, value: T, callback: callable(T): void} $spec
 */
function run(array $spec): void {}

function increment(): void
{
	$i = 0;
	run([
		'first' => $i++,
		'value' => $i,
		'callback' => function ($value): void { assertType('mixed', $value); },
	]);
}

function assignment(): void
{
	$value = 1;
	run([
		'first' => $value = 'str',
		'value' => $value,
		'callback' => function ($value): void { assertType('mixed', $value); },
	]);
}

function nested(): void
{
	$i = 0;
	run([
		'first' => [$i++],
		'value' => $i,
		'callback' => function ($value): void { assertType('mixed', $value); },
	]);
}

function mutate(int &$value): void { $value++; }

function byReferenceArgument(): void
{
	$i = 0;
	run([
		'first' => mutate($i),
		'value' => $i,
		'callback' => function ($value): void { assertType('mixed', $value); },
	]);
}

function keyEffect(): void
{
	$i = 0;
	run([
		($i++ === 0 ? 'first' : 'first') => $i,
		'value' => $i,
		'callback' => function ($value): void { assertType('mixed', $value); },
	]);
}

function literalAfterEffect(): void
{
	$i = 0;
	run([
		'first' => $i++,
		'value' => 42,
		'callback' => function ($value): void { assertType('42', $value); },
	]);
}

class Impure
{

	public int $prop = 5;

	public function reset(): void { $this->prop = 7; }

	public function test(): void
	{
		if ($this->prop !== 5) {
			return;
		}
		run([
			'first' => $this->reset(),
			'value' => $this->prop,
			'callback' => function ($value): void { assertType('mixed', $value); },
		]);
	}

}

function deferredBody(): void
{
	$i = 0;
	run([
		'first' => function () use (&$i): void { $i++; },
		'value' => $i,
		'callback' => function ($value): void { assertType('0', $value); },
	]);
}
