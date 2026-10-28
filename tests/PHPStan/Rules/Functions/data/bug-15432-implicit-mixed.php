<?php // lint >= 8.1

namespace Bug15432ImplicitMixed;

/**
 * @template T
 * @param array{first: mixed, value: T, callback: callable(T): void} $spec
 */
function run(array $spec): void
{
}

/**
 * @template T
 * @param array{producer: callable(): T, consumer: callable(T): void} $pair
 */
function pipe(array $pair): void
{
}

function takesInt(int $i): void
{
}

function takesString(string $s): void
{
}

function earlierItemsWithEffects(): void
{
	$i = 0;
	run([
		'first' => $i++,
		'value' => $i,
		'callback' => function ($v): void {
			takesInt($v);
		},
	]);

	$x = 1;
	run([
		'first' => $x = 'str',
		'value' => $x,
		'callback' => function ($v): void {
			takesString($v);
		},
	]);
}

function callables(): void
{
	pipe([
		'producer' => 'time'(...),
		'consumer' => function ($v): void {
			takesInt($v);
		},
	]);

	pipe([
		'producer' => (new class {
			public function produce(): string
			{
				return 'a';
			}
		})->produce(...),
		'consumer' => function ($v): void {
			takesString($v);
		},
	]);
}
