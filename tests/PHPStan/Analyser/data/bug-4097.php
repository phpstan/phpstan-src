<?php

namespace Bug4097;

class Snapshot {}
class Fu {}
class Bar {}

/**
 * @template T
 */
class SnapshotRepository
{
	/**
	 * @return \Traversable<Snapshot>
	 */
	public function findAllSnapshots(): \Traversable
	{
		yield from \array_map(
			\Closure::fromCallable([$this, 'buildSnapshot']),
			[]
		);
	}

	/**
	 * @param Fu|Bar $entity
	 * @phpstan-param T $entity
	 */
	public function buildSnapshot($entity): Snapshot
	{
		return new Snapshot();
	}
}
