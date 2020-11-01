<?php

namespace Bug4017;

/**
 * @template T
 */
interface DoctrineEntityRepository
{

}

interface DoctrineEntityManagerInterface
{
	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return DoctrineEntityRepository<T>
	 */
	public function getRepository(string $className): DoctrineEntityRepository;
}


/**
 * @phpstan-template TEntityClass
 * @phpstan-extends DoctrineEntityRepository<TEntityClass>
 */
interface MyEntityRepositoryInterface extends DoctrineEntityRepository
{
}

interface MyEntityManagerInterface extends DoctrineEntityManagerInterface
{
	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return MyEntityRepositoryInterface<T>
	 */
	public function getRepository(string $className): MyEntityRepositoryInterface;
}
