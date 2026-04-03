<?php

namespace Bug14429;

interface IEntity {
	/**
	 * @return IRepository<IEntity>
	 */
	public function getRepository(): IRepository;
}

interface IProperty {}

interface IPropertyContainer extends IProperty {}

/**
 * @template E of IEntity
 */
interface IEntityAwareProperty extends IProperty {}

/**
 * @template E of IEntity
 * @extends IEntityAwareProperty<E>
 */
interface IRelationshipContainer extends IPropertyContainer, IEntityAwareProperty {}

interface IModel {
	/**
	 * @template E of IEntity
	 * @template T of IRepository<E>
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getRepository(string $className): IRepository;
}

/**
 * @template E of IEntity
 */
interface IRepository {
	public function getModel(): IModel;
}

class PropertyRelationshipMetadata {
	/** @var class-string<IRepository<IEntity>> */
	public string $repository;
}

/**
 * @template E of IEntity
 * @implements IRelationshipContainer<E>
 */
class HasOne implements IRelationshipContainer
{
	/** @var E|null */
	protected ?IEntity $parent = null;

	/** @var IRepository<E>|null */
	protected ?IRepository $targetRepository = null;

	protected PropertyRelationshipMetadata $metadataRelationship;

	/**
	 * @return E
	 */
	protected function getParentEntity(): IEntity
	{
		return $this->parent ?? throw new \InvalidArgumentException('Relationship is not attached to a parent entity.');
	}

	/**
	 * @return IRepository<E>
	 */
	protected function getTargetRepository(): IRepository
	{
		if ($this->targetRepository === null) {
			/** @var IRepository<E> $targetRepository */
			$targetRepository = $this->getParentEntity()
				->getRepository()
				->getModel()
				->getRepository($this->metadataRelationship->repository);
			$this->targetRepository = $targetRepository;
		}

		return $this->targetRepository;
	}
}
