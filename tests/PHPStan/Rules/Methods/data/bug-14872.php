<?php declare(strict_types = 1);

namespace Bug14872;

/**
 * @template T
 */
class Column
{
}

/**
 * @extends Column<int>
 */
class IntColumn extends Column
{
}

/**
 * @extends Column<string>
 */
class StringColumn extends Column
{
}

class Board
{
	public IntColumn $id;

	public StringColumn $title;
}

class Builder
{

	/**
	 * @template T
	 * @param Column<T> $column
	 * @param T $value
	 */
	public function where($column, string $operator, $value): self
	{
		return $this;
	}

}

function test(Builder $b, Board $board): void
{
	// T is anchored to int by the invariant Column<int>, so the mismatch
	// is reported on the $value argument, not on $column.
	$b->where($board->id, '=', 'test_string_value');
	$b->where($board->title, '=', 3);

	// matching values are accepted
	$b->where($board->id, '=', 5);
	$b->where($board->title, '=', 'ok');
}
