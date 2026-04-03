<?php

declare(strict_types = 1);

namespace Bug14429VarTag;

/** @template E of IEntity */
class ICollection {}

class IEntity {
	/** @return ICollection<IEntity> */
	public function getCollection(): ICollection { return new ICollection(); }
}

/**
 * @template E of IEntity
 */
class OneHasOne
{
	/**
	 * @return ICollection<E>
	 */
	protected function createCollection(IEntity $e): ICollection
	{
		/** @var ICollection<E> $collection */
		$collection = $e->getCollection();
		return $collection;
	}
}
