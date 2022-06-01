<?php

namespace Bug7344;

use Exception;
use function PHPStan\Testing\assertType;

interface PhpdocTypeInterface
{
	/**
	 * Interface for phpdoc type for Phpstan only
	 *
	 * @return never
	 */
	public function neverImplement(): void;
}

interface IsEntity extends PhpdocTypeInterface
{
}

class Model
{
	/**
	 * @return static
	 * @phpstan-return static&IsEntity
	 */
	public function createEntity(): self
	{
		throw new Exception();
	}

	/**
	 * @return static
	 */
	public function getModel(bool $allowOnModel = false): self
	{
		throw new Exception();
	}
}

class Person extends Model
{
}

class Female extends Person
{
}

class Male extends Person
{
}

class Foo
{
	public function doFoo(): void
	{
		// simple type
		$model = new Model();
		assertType('Bug7344\Model', $model);
		$entity = $model->createEntity();
		assertType('Bug7344\IsEntity&Bug7344\Model', $entity);
		assertType('Bug7344\Model', $entity->getModel());
		assertType('Bug7344\Model', random_int(0, 1) === 0 ? $model : $entity);

		// simple type II
		$model = new Male();
		assertType('Bug7344\Male', $model);
		$entity = $model->createEntity();
		assertType('Bug7344\IsEntity&Bug7344\Male', $entity);
		assertType('Bug7344\Male', $entity->getModel());
		assertType('Bug7344\Male', random_int(0, 1) === 0 ? $model : $entity);

		// union type
		$modelClass = random_int(0, 1) === 0 ? Female::class : Male::class;
		$model = new $modelClass();
		assertType('Bug7344\Female|Bug7344\Male', $model);
		$entity = $model->createEntity();
		assertType('(Bug7344\Female&Bug7344\IsEntity)|(Bug7344\IsEntity&Bug7344\Male)', $entity);
		assertType('Bug7344\Female|Bug7344\Male', $entity->getModel());
		assertType('Bug7344\Female|Bug7344\Male', random_int(0, 1) === 0 ? $model : $entity);
	}
}
