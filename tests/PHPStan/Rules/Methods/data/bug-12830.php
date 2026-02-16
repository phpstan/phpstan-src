<?php declare(strict_types=1);

namespace Bug12830;

interface I
{
	public function __construct(string $mustBeString);
}

class A implements I
{
	public string $MustBeString;
	public int $CanBeInt;

	public function __construct(string $mustBeString, int $canBeInt = -1)
	{
		$this->MustBeString = $mustBeString;
		$this->CanBeInt = $canBeInt;
	}
}

class B extends A
{
	public bool $CanBeBool;

	public function __construct(string $mustBeString, bool $canBeBool = false)
	{
		$this->MustBeString = $mustBeString;
		$this->CanBeBool = $canBeBool;
	}
}

var_dump([
	new A('A', 1),
	new B('B', true),
]);
