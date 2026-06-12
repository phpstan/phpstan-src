<?php declare(strict_types=1); // lint >= 8.4

namespace ReadonlyPropertyHooks;

class HelloWorld
{
	public readonly string $firstName {
		get => $this->firstName;
		set => $this->firstName;
	}

	public readonly string $middleName { get => $this->middleName; }

	public readonly string $lastName { set => $this->lastName; }
}

abstract class HiWorld
{
	public abstract readonly string $firstName { get { return 'jake'; } set; }
}

readonly class GoodMorningWorld
{
	public string $firstName {
		get => $this->firstName;
		set => $this->firstName;
	}
}
