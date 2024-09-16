<?php

namespace Bug11032;

/**
 * @template-covariant T
 */
interface PromiseInterface
{
}

/**
 * @template T
 */
final class Deferred
{
	/**
	 * @var PromiseInterface<T>
	 */
	private $promise = null;

	/**
	 * @return PromiseInterface<T>
	 */
	public function promise(): PromiseInterface
	{
		return $this->promise;
	}
}

/**
 * @template T
 * @param iterable<T> $tasks
 * @return PromiseInterface<array<T>>
 */
function parallel(iterable $tasks): PromiseInterface
{
	/** @var Deferred<array<T>> $deferred*/
	$deferred = new Deferred();

	return $deferred->promise();
}
