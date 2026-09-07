<?php declare(strict_types = 1);

namespace UnconstrainedQueryResult;

use Iterator;
use IteratorAggregate;
use LogicException;
use stdClass;
use Traversable;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Query
{
}

class QueryBuilder
{
}

/**
 * @template-covariant TValue
 * @implements IteratorAggregate<int, TValue>
 */
class QueryResultSet implements IteratorAggregate
{

	public function __construct(Query $query)
	{
	}

	/** @return Traversable<int, TValue> */
	public function getIterator(): Traversable
	{
		throw new LogicException();
	}

	/** @return iterable<int, TValue> */
	public function toIterable(): iterable
	{
		throw new LogicException();
	}

	/** @return array<int, TValue> */
	public function toArray(): array
	{
		throw new LogicException();
	}

}

/**
 * @template-covariant T
 * @implements Iterator<int, T>
 */
class BatchIterator implements Iterator
{

	public function __construct(QueryBuilder $queryBuilder, string $uniqueIdColumn, int $batchSize = 100)
	{
	}

	/** @return T */
	public function current()
	{
		throw new LogicException();
	}

	public function key(): int
	{
		throw new LogicException();
	}

	public function next(): void
	{
	}

	public function rewind(): void
	{
	}

	public function valid(): bool
	{
		throw new LogicException();
	}

}

function queryResultSet(Query $query): void
{
	$results = new QueryResultSet($query);
	assertType('UnconstrainedQueryResult\QueryResultSet<mixed>', $results);
	assertNativeType('UnconstrainedQueryResult\QueryResultSet<mixed>', $results);
	assertType('array<int, mixed>', $results->toArray());
	assertType('iterable<int, mixed>', $results->toIterable());

	foreach ($results->toIterable() as $row) {
		assertType('mixed', $row);
		assertNativeType('mixed', $row);
	}

	/** @var stdClass $row */
	foreach ($results->toIterable() as $row) {
		assertType('stdClass', $row);
	}
}

function batchIterator(QueryBuilder $queryBuilder): void
{
	$rows = new BatchIterator($queryBuilder, 'f.id', 5000);
	assertType('UnconstrainedQueryResult\BatchIterator<mixed>', $rows);
	assertNativeType('UnconstrainedQueryResult\BatchIterator<mixed>', $rows);

	foreach ($rows as $row) {
		assertType('mixed', $row);
		assertNativeType('mixed', $row);
	}

	/** @var stdClass $row */
	foreach ($rows as $row) {
		assertType('stdClass', $row);
	}
}
