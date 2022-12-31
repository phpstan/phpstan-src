<?php declare(strict_types = 1);

namespace Bug8586;

class Foo
{
	public function getString(): ?string
	{
		return '';
	}
}

/**
 * @method void refreshFromAnnotation(object $object)
 */
class EntityManager
{
	public function refresh(object $object): void
	{
	}
}

class HelloWorld
{
	public function sayHello(Foo $foo, EntityManager $em): void
	{
		\assert($foo->getString() === null);
		$em->refreshFromAnnotation($foo);
		\assert($foo->getString() !== null);
	}

	public function sayHello2(Foo $foo, EntityManager $em): void
	{
		\assert($foo->getString() === null);
		$em->refresh($foo);
		\assert($foo->getString() !== null);
	}
}
