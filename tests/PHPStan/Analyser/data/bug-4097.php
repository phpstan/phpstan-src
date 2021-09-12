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

	/** @var T[] */
	private $entities;

	/**
	 * @param T[] $entities
	 */
	public function __construct(array $entities)
	{
		$this->entities = $entities;
	}

	/**
	 * @return \Traversable<Snapshot>
	 */
	public function findAllSnapshots(): \Traversable
	{
		yield from \array_map(
			\Closure::fromCallable([$this, 'buildSnapshot']),
			$this->entities
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
