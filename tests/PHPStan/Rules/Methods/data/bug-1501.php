<?php declare(strict_types = 1);

namespace Bug1501;

class HelloWorld
{
	public function sayHello(): void
	{
		$so = new SomeObject();
		$this->modify($so->getForModify());
	}

	private function modify(array &$data): void
	{
		$data[] = 'abc';
	}
}

class SomeObject
{
	private $someVar;

	public function __construct()
	{
		$this->someVar = [];
	}

	public function &getForModify(): array
	{
		return $this->someVar;
	}
}
