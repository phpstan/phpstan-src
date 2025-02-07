<?php declare(strict_types=1);

namespace VirtualHookedProperties;

class HelloWorld
{
	public string $firstName = 'John' {
		get => $this->firstName;
		set => $this->firstName = $value;
	}

	public string $middleName {
		get => $this->middleName;
		set => $this->middleName = $value;
	}

	public string $lastName = 'Doe' {
		get => 'Smith';
	}

	public string $maidenName = 'Brown';
}
