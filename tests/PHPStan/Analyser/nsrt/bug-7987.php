<?php declare(strict_types=1);

namespace Bug7987;

use function PHPStan\Testing\assertType;

class AbstractA{}
class AbstractB{}

class Factory
{
	/**
	 * @template TypeObject of AbstractA|AbstractB
	 * @param  class-string<TypeObject> $objectClass
	 * @return TypeObject
	 */
	public function getObject(string $objectClass): AbstractA|AbstractB
	{
		if (is_subclass_of($objectClass, AbstractA::class)) {
			assertType('class-string<TypeObject of Bug7987\AbstractA (method Bug7987\Factory::getObject(), argument)>', $objectClass);
			$object = $this->getObjectA($objectClass);
			assertType('TypeObject of Bug7987\AbstractA (method Bug7987\Factory::getObject(), argument)', $object);
		} elseif (is_subclass_of($objectClass, AbstractB::class)) {
			assertType('class-string<TypeObject of Bug7987\AbstractB (method Bug7987\Factory::getObject(), argument)>', $objectClass);
			$object = $this->getObjectB($objectClass);
			assertType('TypeObject of Bug7987\AbstractB (method Bug7987\Factory::getObject(), argument)', $object);
		} else {
			throw new \Exception("unable to instantiate $objectClass");
		}
		assertType('TypeObject of Bug7987\AbstractA (method Bug7987\Factory::getObject(), argument)|TypeObject of Bug7987\AbstractB (method Bug7987\Factory::getObject(), argument)', $object);
		return $object;
	}

	/**
	 * @template TypeObject of AbstractA
	 * @param class-string<TypeObject> $objectClass
	 * @return TypeObject
	 */
	private function getObjectA(string $objectClass): AbstractA
	{
		return new $objectClass();
	}

	/**
	 * @template TypeObject of AbstractB
	 * @param class-string<TypeObject> $objectClass
	 * @return TypeObject
	 */
	private function getObjectB(string $objectClass): AbstractB
	{
		return new $objectClass();
	}
}
