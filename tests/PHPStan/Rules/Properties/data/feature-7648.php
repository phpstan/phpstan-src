<?php declare(strict_types = 1); // lint >= 8.1

namespace Feature7648;

/** @immutable */
class Request
{
	use OffsetTrait;

	public function __construct(int $offset)
	{
		$this->populateOffsets($offset);
	}
}

/** @immutable */
trait OffsetTrait
{
	public readonly int $offset;

	private function populateOffsets(int $offset): void
	{
		$this->offset = $offset;
	}
}
