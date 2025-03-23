<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug6828;

/** @template T */
interface Option
{
	/**
	 * @template U
	 * @param \Closure(T):U $c
	 * @return Option<U>
	 */
	function map(\Closure $c);
}

/**
 * @template T
 * @template E
 */
abstract class Result
{
	/** @return T */
	function unwrap()
	{

	}

	/**
	 * @template U
	 * @param U $v
	 * @return Result<U, mixed>
	 */
	static function ok($v)
	{

	}
}

/**
 * @template U
 * @template F
 * @param Result<Option<U>, F> $result
 * @return Option<Result<U, F>>
 */
function f(Result $result): Option
{
	/** @var Option<Result<U, F>> */
	return $result->unwrap()->map(Result::ok(...));
}
