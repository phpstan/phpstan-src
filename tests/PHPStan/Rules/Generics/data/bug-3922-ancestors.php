<?php

namespace Bug3922Ancestors;

/**
 * @template TResult
 * @template TQuery of QueryInterface<TResult>
 */
interface QueryHandlerInterface
{
	/**
	 * @param TQuery $query
	 *
	 * @return TResult
	 */
	public function handle(QueryInterface $query);
}

/**
 * @template TResult
 */
interface QueryInterface
{
}

/**
 * @template-implements QueryInterface<string>
 */
final class FooQuery implements QueryInterface
{
}

/**
 * @template-implements QueryInterface<int>
 */
final class BarQuery implements QueryInterface
{
}

/**
 * @template-implements QueryHandlerInterface<string, FooQuery>
 */
final class FooQueryHandler implements QueryHandlerInterface
{
	public function handle(QueryInterface $query): string
	{
		return 'foo';
	}
}

/**
 * @template-implements QueryHandlerInterface<string, BarQuery>
 */
final class BarQueryHandler implements QueryHandlerInterface
{
	public function handle(QueryInterface $query): int
	{
		return 10;
	}
}
