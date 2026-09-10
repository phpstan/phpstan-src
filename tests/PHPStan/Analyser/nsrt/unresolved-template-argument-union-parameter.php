<?php // lint >= 8.0

namespace UnresolvedTemplateArgumentUnionParameter;

use Throwable;
use function PHPStan\Testing\assertType;

/**
 * @template-covariant T
 */
interface PromiseInterface
{

	/**
	 * @template TFulfilled
	 * @template TRejected
	 * @param ?(callable((T is void ? null : T)): (PromiseInterface<TFulfilled>|TFulfilled)) $onFulfilled
	 * @param ?(callable(Throwable): (PromiseInterface<TRejected>|TRejected)) $onRejected
	 * @return PromiseInterface<($onRejected is null ? ($onFulfilled is null ? T : TFulfilled) : ($onFulfilled is null ? T|TRejected : TFulfilled|TRejected))>
	 */
	public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface;

}

/**
 * @template T
 * @param iterable<PromiseInterface<T>|T> $promisesOrValues
 * @return PromiseInterface<array<T>>
 */
function all(iterable $promisesOrValues): PromiseInterface
{
	throw new \Exception();
}

/**
 * @template T
 * @param T|null $value
 * @return T|null
 */
function nullable(mixed $value): mixed
{
	return $value;
}

class Foo
{

	/**
	 * @param PromiseInterface<int|null> $promise
	 */
	public function fulfilled(PromiseInterface $promise): void
	{
		$list = [$promise->then(static fn (?int $value): string => '')];
		assertType('array{UnresolvedTemplateArgumentUnionParameter\PromiseInterface<\'\'>}', $list);
		assertType('UnresolvedTemplateArgumentUnionParameter\PromiseInterface<array<\'\'>>', all($list));
	}

	/**
	 * @param PromiseInterface<int|null> $promise
	 * @return PromiseInterface<array<int|void|null>|null>
	 */
	public function rejected(PromiseInterface $promise): PromiseInterface
	{
		$list = [$promise->then(onRejected: static function (Throwable $e): void {
			if ($e instanceof \RuntimeException) {
				throw $e;
			}
		})];
		assertType('array{UnresolvedTemplateArgumentUnionParameter\PromiseInterface<int|void|null>}', $list);
		assertType('UnresolvedTemplateArgumentUnionParameter\PromiseInterface<array<int|void|null>>', all($list));

		return all($list);
	}

	/**
	 * @param PromiseInterface<int|null> $promise
	 */
	public function nakedMemberStillBinds(PromiseInterface $promise): void
	{
		assertType('UnresolvedTemplateArgumentUnionParameter\PromiseInterface<int|null>|null', nullable($promise));
	}

}
