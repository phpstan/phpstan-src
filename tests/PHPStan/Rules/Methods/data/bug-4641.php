<?php

namespace Bug4641;

interface IEntity
{

}

/** @template E of IEntity */
interface IRepository
{

}

interface I
{

	/**
	 * @template E of IEntity
	 * @template T of IRepository<E>
	 * @template U
	 * @phpstan-param class-string<T> $className
	 * @phpstan-return T
	 */
	function getRepository(string $className): IRepository;

}
