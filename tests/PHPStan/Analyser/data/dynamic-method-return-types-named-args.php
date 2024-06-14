<?php // onlyif PHP_VERSION_ID >= 80000

namespace DynamicMethodReturnTypesNamespace;

use function PHPStan\Testing\assertType;

class FooNamedArgs
{

	public function __construct()
	{
	}

	public function doFoo()
	{
		$em = new EntityManager();
		$iem = new InheritedEntityManager();

		assertType('DynamicMethodReturnTypesNamespace\Foo', $em->getByPrimary(className: \DynamicMethodReturnTypesNamespace\Foo::class));
		assertType('DynamicMethodReturnTypesNamespace\Foo', $iem->getByPrimary(className: \DynamicMethodReturnTypesNamespace\Foo::class));

		assertType('DynamicMethodReturnTypesNamespace\Foo', \DynamicMethodReturnTypesNamespace\EntityManager::createManagerForEntity(className: \DynamicMethodReturnTypesNamespace\Foo::class));
		assertType('DynamicMethodReturnTypesNamespace\Foo', \DynamicMethodReturnTypesNamespace\InheritedEntityManager::createManagerForEntity(className: \DynamicMethodReturnTypesNamespace\Foo::class));
	}

}
