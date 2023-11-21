<?php

namespace DynamicMethodReturnTypesNamespace;

use function PHPStan\Testing\assertType;

class EntityManager
{

	public function getByPrimary(string $className, int $id): Entity
	{
		return new $className();
	}

	public static function createManagerForEntity(string $className): self
	{

	}

}

class InheritedEntityManager extends EntityManager
{

}

class ComponentContainer implements \ArrayAccess
{

	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{

	}

	public function offsetGet($offset): Entity
	{

	}

	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{

	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{

	}

}

class Foo
{

	public function __construct()
	{
	}

	public function doFoo()
	{
		$em = new EntityManager();
		$iem = new InheritedEntityManager();
		$container = new ComponentContainer();

		assertType('*ERROR*', $em->getByFoo($foo));
		assertType('DynamicMethodReturnTypesNamespace\Entity', $em->getByPrimary());
		assertType('DynamicMethodReturnTypesNamespace\Entity', $em->getByPrimary($foo));
		assertType('DynamicMethodReturnTypesNamespace\Foo', $em->getByPrimary(\DynamicMethodReturnTypesNamespace\Foo::class));

		assertType('*ERROR*', $iem->getByFoo($foo));
		assertType('DynamicMethodReturnTypesNamespace\Entity', $iem->getByPrimary());
		assertType('DynamicMethodReturnTypesNamespace\Entity', $iem->getByPrimary($foo));
		assertType('DynamicMethodReturnTypesNamespace\Foo', $iem->getByPrimary(\DynamicMethodReturnTypesNamespace\Foo::class));

		assertType('*ERROR*', EntityManager::getByFoo($foo));
		assertType('DynamicMethodReturnTypesNamespace\EntityManager', \DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity());
		assertType('DynamicMethodReturnTypesNamespace\EntityManager', \DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity($foo));
		assertType('DynamicMethodReturnTypesNamespace\Foo', \DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity(\DynamicMethodReturnTypesNamespace\Foo::class));

		assertType('*ERROR*', InheritedEntityManager::getByFoo($foo));
		assertType('DynamicMethodReturnTypesNamespace\EntityManager', \DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity());
		assertType('DynamicMethodReturnTypesNamespace\EntityManager', \DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity($foo));
		assertType('DynamicMethodReturnTypesNamespace\Foo', \DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity(\DynamicMethodReturnTypesNamespace\Foo::class));

		assertType('DynamicMethodReturnTypesNamespace\Foo', $container[\DynamicMethodReturnTypesNamespace\Foo::class]);
		assertType('object', new \DynamicMethodReturnTypesNamespace\Foo());
		assertType('object', new \DynamicMethodReturnTypesNamespace\FooWithoutConstructor());
		assertType('bool', \DynamicMethodReturnTypesNamespace\WhateverClass::methodReturningBoolNoMatterTheCaller());
		assertType('bool', \DynamicMethodReturnTypesNamespace\WhateverClass2::methodReturningBoolNoMatterTheCaller());
	}

}

class FooWithoutConstructor
{

}


class WhateverClass {
	public static function methodReturningBoolNoMatterTheCaller(){}
}
class WhateverClass2 {
	public static function methodReturningBoolNoMatterTheCaller(){}
}
