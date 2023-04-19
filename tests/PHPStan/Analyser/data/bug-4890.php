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
		assertType('', get_class($entity));

		if ($entity instanceof Proxy) {
			assertType('', get_class($entity));
		}

		$class = $entity instanceof Proxy
			? get_parent_class($entity)
			: get_class($entity);
		assert(is_string($class));

	}
}
