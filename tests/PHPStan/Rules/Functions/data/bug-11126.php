<?php

namespace Bug11126;

/**
 * @template T
 */
class Collection {
	/**
	 * @template TRet
	 * @param (callable(T): TRet) $callback
	 * @return static<TRet>
	 */
	public function map(callable $callback): Collection {
		return $this;
	}
}

/**
 * @param Collection<int<0, max>> $in
 * @return Collection<int<0, max>>
 */
function foo(Collection $in): Collection {
	return $in->map(static fn ($v) => $v);
}

/**
 * @param Collection<int<0, max>> $in
 * @return Collection<int<0, max>>
 */
function bar(Collection $in): Collection {
	return $in->map(value(...));
}

/**
 * @param int<0, max> $in
 * @return int<0, max>
 */
function value(int $in): int {
	return $in;
}
