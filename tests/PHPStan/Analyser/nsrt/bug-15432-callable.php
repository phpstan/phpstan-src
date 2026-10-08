<?php // lint >= 8.1

namespace Bug15432Callable;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param array{producer: callable(): T, consumer: callable(T): void} $pair
 */
function pipe(array $pair): void {}

class Producer
{

	public function produce(): int { return 1; }
	public static function produceStatic(): int { return 1; }

}

function test(Producer $producer): void
{
	pipe([
		'producer' => $producer->produce(...),
		'consumer' => function ($value): void { assertType('int', $value); },
	]);
	$callable = $producer->produce(...);
	pipe([
		'producer' => $callable,
		'consumer' => function ($value): void { assertType('int', $value); },
	]);
	pipe([
		'producer' => Producer::produceStatic(...),
		'consumer' => function ($value): void { assertType('int', $value); },
	]);
}

function dynamicName(Producer $producer, string $name): void
{
	pipe([
		'producer' => $producer->{$name}(...),
		'consumer' => function ($value): void { assertType('mixed', $value); },
	]);
}

function dynamicStatic(string $className, string $methodName): void
{
	pipe([
		'producer' => $className::{$methodName}(...),
		'consumer' => function ($value): void { assertType('mixed', $value); },
	]);
}
