<?php // lint >= 8.0

namespace Bug15448Dependency;

class Error
{
}

/**
 * @template T of Error|ErrorIterator
 * @implements \Iterator<int, T>
 */
class ErrorIterator implements \Iterator
{

	/** @param list<T> $errors */
	public function __construct(private array $errors)
	{
	}

	/** @return T */
	public function current(): Error|self
	{
		return $this->errors[0];
	}

	public function next(): void
	{
	}

	public function key(): int
	{
		return 0;
	}

	public function valid(): bool
	{
		return false;
	}

	public function rewind(): void
	{
	}

}
