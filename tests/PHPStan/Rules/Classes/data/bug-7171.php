<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7171;

/**
 * @template T of object
 */
class EntityRepository
{
}

/**
 * @template T of object
 * @template-extends EntityRepository<T>
 */
class ServiceEntityRepository extends EntityRepository
{
}

/**
 * @extends ServiceEntityRepository<MyEntityAttribute>
 */
class MyRepositoryAttribute extends ServiceEntityRepository
{
}

/**
 * @extends ServiceEntityRepository<MyEntityExtend>
 */
class MyRepositoryExtend extends ServiceEntityRepository
{
}

/**
 * @template T of object
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Entity
{
	/**
	 * @param class-string<EntityRepository<T>>|null $repositoryClass
	 */
	public function __construct(?string $repositoryClass = null)
	{
		// Logic
		echo $repositoryClass;
	}
}

#[Entity(repositoryClass: MyRepositoryAttribute::class)]
class MyEntityAttribute
{
}

/**
 * @extends Entity<MyEntityExtend>
 */
class MyEntityExtend extends Entity
{
	public function __construct()
	{
		parent::__construct(repositoryClass: MyRepositoryExtend::class);
	}
}

#[Entity(repositoryClass: \stdClass::class)]
class WrongEntity
{
}
