<?php

namespace Bug4890b;

interface Proxy {}

class HelloWorld
{
	public function update(object $entity): void
	{
		assert(method_exists($entity, 'getId'));

		$class = $entity instanceof Proxy
			? get_parent_class($entity)
			: get_class($entity);
		assert(is_string($class));
	}
}


