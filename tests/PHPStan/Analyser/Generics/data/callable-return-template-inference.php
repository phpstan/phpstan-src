<?php // lint >= 8.0
declare(strict_types = 1);

namespace CallableReturnTemplateInference;

use function PHPStan\Testing\assertType;

/**
 * @template K
 * @template V
 */
class Pair
{

	/**
	 * @param K $key
	 * @param V $value
	 */
	public function __construct(public mixed $key, public mixed $value)
	{
	}

}

/**
 * @template K
 * @template V
 */
class Map
{
}

/**
 * @template K
 * @template V
 * @template KReturn
 * @template VReturn
 * @param iterable<K, V> $items
 * @param callable(K, V): Pair<KReturn, VReturn> $mapper
 * @return Map<KReturn, VReturn>
 */
function mapItems(iterable $items, callable $mapper): Map
{
	return new Map();
}

/** @param array<string, int> $items */
function callbacks(array $items): void
{
	assertType('CallableReturnTemplateInference\Map<string, int>', mapItems(
		$items,
		static fn (string $key, int $value) => new Pair($key, $value),
	));

	assertType('CallableReturnTemplateInference\Map<string, int>', mapItems(
		$items,
		static function (string $key, int $value): Pair {
			return new Pair($key, $value);
		},
	));

	assertType('CallableReturnTemplateInference\Map<string, CallableReturnTemplateInference\Pair<int, int>>', mapItems(
		$items,
		static fn (string $key, int $value) => new Pair($key, new Pair($value, $value)),
	));
}

/** @template T */
class Invoker
{

	/** @param T $value */
	public function __construct(public mixed $value)
	{
	}

	/** @return self<T> */
	public function __invoke(): self
	{
		return $this;
	}

}

/**
 * @template T
 * @param Invoker<T> $callback
 * @return Invoker<T>
 */
function passInvoker(Invoker $callback): Invoker
{
	return $callback;
}

function recursiveCallable(int $value): void
{
	assertType('CallableReturnTemplateInference\Invoker<int>', passInvoker(new Invoker($value)));
}
