<?php

namespace Bug4890;

use function PHPStan\Testing\assertType;

interface Proxy {}

class HelloWorld
{
	public function update(object $entity): void
	{
		assertType('class-string<object>', get_class($entity));
		assert(method_exists($entity, 'getId'));
		assertType('class-string<object&hasMethod(getId)>', get_class($entity));

		if ($entity instanceof Proxy) {
			assertType('class-string<Bug4890\Proxy&hasMethod(getId)>', get_class($entity));
		}

		$class = $entity instanceof Proxy
			? get_parent_class($entity)
			: get_class($entity);
		assert(is_string($class));

	}

	public function updateProp(object $entity): void
	{
		assertType('class-string<object>', get_class($entity));
		assert(property_exists($entity, 'myProp'));
		assertType('class-string<object&hasProperty(myProp)>', get_class($entity));

		if ($entity instanceof Proxy) {
			assertType('class-string<Bug4890\Proxy&hasProperty(myProp)>', get_class($entity));
		}

		$class = $entity instanceof Proxy
			? get_parent_class($entity)
			: get_class($entity);
		assert(is_string($class));
	}

	/**
	 * @param object{foo: self, bar: int, baz?: string} $entity
	 */
	public function updateObjectShape($entity): void
	{
		assertType('class-string', get_class($entity));
		assert(property_exists($entity, 'foo'));
		assertType('class-string', get_class($entity));
	}
}
