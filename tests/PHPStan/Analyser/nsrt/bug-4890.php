<?php

namespace Bug4890;

use function PHPStan\Testing\assertType;

interface Proxy {}

class HelloWorld
{
	public function update(object $entity): void
	{
		assertType('class-string', get_class($entity));
		assert(method_exists($entity, 'getId'));
		assertType('class-string<hasMethod(getId)>', get_class($entity));

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
		assertType('class-string', get_class($entity));
		assert(property_exists($entity, 'myProp'));
		assertType('class-string<hasProperty(myProp)>', get_class($entity));

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
		assertType('class-string<object{foo: Bug4890\HelloWorld, bar: int, baz?: string}>', get_class($entity));
		assert(property_exists($entity, 'foo'));
		assertType('class-string<object{foo: Bug4890\HelloWorld, bar: int, baz?: string}>', get_class($entity));
	}

	/**
	 * @phpstan-template T of Proxy
	 *
	 * @param T $entity
	 *
	 * @return T
	 */
	public function updateGeneric($entity): object
	{
		assertType('class-string<T of Bug4890\Proxy (method Bug4890\HelloWorld::updateGeneric(), argument)>', get_class($entity));
		assert(property_exists($entity, 'myProp'));
		assertType('class-string<T of Bug4890\Proxy (method Bug4890\HelloWorld::updateGeneric(), argument)&hasProperty(myProp)>', get_class($entity));

		return $entity;
	}
}
