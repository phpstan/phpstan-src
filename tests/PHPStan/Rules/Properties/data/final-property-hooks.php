<?php declare(strict_types=1);

namespace FinalPropertyHooks;

class HelloWorld
{
	public final string $firstName {
		get => $this->firstName;
		set => $this->firstName;
	}

	public final string $middleName { get => $this->middleName; }

	public final string $lastName { set => $this->lastName; }
}

abstract class HiWorld
{
	public abstract final string $firstName { get { return 'jake'; } set; }
}

final class GoodMorningWorld
{
	public string $firstName {
		get => $this->firstName;
		set => $this->firstName;
	}
}
