<?php

namespace TemplateReturnVariance;

use function PHPStan\Testing\assertType;

/** @template-contravariant T */
interface Consumer
{

	/** @param T $value */
	public function accept($value): void;

}

/**
 * @template T
 * @param T $value
 * @param callable(T): void $handler
 * @return callable(T): void
 */
function handler($value, callable $handler): callable
{
	return $handler;
}

/**
 * @template T
 * @param T $value
 * @param Consumer<T> $consumer
 * @return Consumer<T>
 */
function consumer($value, Consumer $consumer): Consumer
{
	return $consumer;
}

/**
 * @template T
 * @param T $value
 * @param callable(T): void $handler
 * @return T
 */
function value($value, callable $handler)
{
	return $value;
}

/**
 * @template T
 * @param T $value
 * @param callable(T): void $handler
 * @return array{T, callable(T): void}
 */
function both($value, callable $handler): array
{
	return [$value, $handler];
}

/**
 * @template T
 * @param T $value
 * @param callable(T): void $handler
 * @return callable(callable(T): void): void
 */
function nested($value, callable $handler): callable
{
	return static function (callable $cb) use ($value): void {
		$cb($value);
	};
}

/**
 * @template T
 * @param callable(T): void $handler
 * @return callable(T): void
 */
function onlyHandler(callable $handler): callable
{
	return $handler;
}

/** @param callable(int|string): void $handler */
function takesHandler(callable $handler): void
{
}

/** @param Consumer<int|string> $consumer */
function takesConsumer(Consumer $consumer): void
{
}

/**
 * @param callable(int|string): void $intOrStringHandler
 * @param Consumer<int|string> $intOrStringConsumer
 */
function test(callable $intOrStringHandler, Consumer $intOrStringConsumer): void
{
	// T only in contravariant positions of the return type: the upper bound
	assertType('callable(int|string): void', handler(1, $intOrStringHandler));
	assertType('TemplateReturnVariance\Consumer<int|string>', consumer(1, $intOrStringConsumer));

	$handler = handler(1, $intOrStringHandler);
	takesHandler($handler);
	$consumer = consumer(1, $intOrStringConsumer);
	takesConsumer($consumer);

	// T covariant in the return type, or in both kinds of position: the lower bound
	assertType('1', value(1, $intOrStringHandler));
	assertType('array{1, callable(1): void}', both(1, $intOrStringHandler));
	assertType('callable(callable(1): void): void', nested(1, $intOrStringHandler));

	// no lower bound: the upper bound, as before
	assertType('callable(int|string): void', onlyHandler($intOrStringHandler));
}
