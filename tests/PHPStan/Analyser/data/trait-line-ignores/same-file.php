<?php declare(strict_types = 1);

namespace TraitLineIgnoresSameFile;

trait FooTrait
{

	public function getA(): string
	{
		return $this->a; // @phpstan-ignore-line legacy
	}

}

class Foo
{

	use FooTrait;

	public string $a = '';

}

class Bar
{

	use FooTrait;

	public int $a = 1;

}

class Baz
{

	use FooTrait;

}
