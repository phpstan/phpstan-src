<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15771;

use function PHPStan\Testing\assertType;

final class Item {}

/**
 * @template A
 * @template B
 * @param ?callable(): A $a
 * @param ?callable(): B $b
 * @return ($a is null ? never : A)|($b is null ? never : B)
 */
function condcb(?callable $a = null, ?callable $b = null) { throw new \Exception(); }

/** @param list<Item> $l */
function test(array $l): void
{
	assertType('list<Bug15771\Item>', condcb(fn () => $l, fn () => $l));
}

/**
 * @template-covariant ValueType
 * @template-covariant RejectionType of \Throwable
 */
interface Promise
{

	/**
	 * @template OnFulfilledValueType
	 * @template OnFulfilledRejectionType of \Throwable
	 * @template OnRejectedValueType
	 * @template OnRejectedRejectionType of \Throwable
	 *
	 * @param ?callable(ValueType): (Promise<OnFulfilledValueType, OnFulfilledRejectionType>) $onFulfilled
	 * @param ?callable(\Throwable): (Promise<OnRejectedValueType, OnRejectedRejectionType>) $onRejected
	 *
	 * @return Promise<($onFulfilled is null ? ValueType : OnFulfilledValueType)|($onRejected is null ? never : OnRejectedValueType), ($onFulfilled is null ? never : OnFulfilledRejectionType)|($onRejected is null ? RejectionType : OnRejectedRejectionType)>
	 */
	public function then(?callable $onFulfilled = null, ?callable $onRejected = null): Promise;

}

/**
 * @template ValueType
 * @implements Promise<ValueType, never>
 */
final class Fulfilled implements Promise
{

	/** @param ValueType $value */
	public function __construct(private $value)
	{
	}

	/** @return ValueType */
	public function get()
	{
		return $this->value;
	}

	#[\Override]
	public function then(?callable $onFulfilled = null, ?callable $onRejected = null): Promise
	{
		return $this;
	}

}

final class Response
{
}

final class Factory
{

	/** @return list<Item> */
	public function create(Response $response): array
	{
		return [];
	}

}

final class Resolver
{

	public function __construct(private Factory $factory)
	{
	}

	/**
	 * @param Promise<Response, \RuntimeException> $promise
	 * @return Promise<list<Item>, \Throwable>
	 */
	public function process(Promise $promise): Promise
	{
		$result = $promise->then(
			function (Response $r): Fulfilled {
				return new Fulfilled($this->factory->create($r));
			},
			function (\Throwable $e): Fulfilled {
				return new Fulfilled([]);
			},
		);
		assertType('Bug15771\Promise<list<Bug15771\Item>, Throwable>', $result);

		return $result;
	}

}
