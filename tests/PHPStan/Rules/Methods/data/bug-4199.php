<?php // lint >= 8.0

namespace Bug4199;

class Foo
{
	public function getBar(): ?Bar
	{
		return null;
	}
}

class Bar
{
	public function getBaz(): Baz
	{
		return new Baz();
	}
	public function getBazOrNull(): ?Baz
	{
		return null;
	}
}

class Baz
{
	public function answer(): int
	{
		return 42;
	}
}

function (): void {
	$foo = new Foo;
	$answer = $foo->getBar()?->getBaz()->answer();

	$answer2 = $foo->getBar()?->getBazOrNull()->answer();
};
