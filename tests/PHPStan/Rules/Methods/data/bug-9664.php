<?php declare(strict_types = 1);

namespace Bug9664;

class Entity1
{
	public function setFoo(string $foo): void {}
}

class Entity2
{
	public function setFoo(?string $foo): void {}
}

function foo(Entity1|Entity2 $entity): void
{
	$entity->setFoo(null); // Should error: Entity1::setFoo() does not accept null
}

function foo1(Entity1 $entity): void
{
	$entity->setFoo(null); // Error
}

function foo2(Entity2 $entity): void
{
	$entity->setFoo(null); // OK
}
