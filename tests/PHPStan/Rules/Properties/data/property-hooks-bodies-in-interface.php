<?php declare(strict_types=1);

namespace PropertyHooksBodiesInInterface;

interface HelloWorld
{
	public string $firstName {
		get {
			return 'Foo';
		}
	}

	public string $lastName {
		set {
			echo 'I will eventually be set to ' . $value;
		}
	}

	public string $middleName { get; set; }
}
