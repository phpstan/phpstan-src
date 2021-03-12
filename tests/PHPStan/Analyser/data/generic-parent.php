<?php

namespace GenericParent;

use function PHPStan\Analyser\assertType;

interface Animal
{

}

interface Dog extends Animal
{

}

/**
 * @template T of Animal
 */
class Foo
{

	/** @return T */
	public function getAnimal(): Animal
	{

	}

}

/** @extends Foo<Dog> */
class Bar extends Foo
{

	public function doFoo()
	{
		assertType(Dog::class, parent::getAnimal());
		assertType(Dog::class, Foo::getAnimal());
	}

}
