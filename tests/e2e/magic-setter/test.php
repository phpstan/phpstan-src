<?php // lint >= 7.4

namespace MagicSetter;

class Foo
{

	private string $name;

	public function __construct(string $name)
	{
		$this->setName($name); // magic setter
	}

	public function getName(): string
	{
		return $this->name;
	}

}
