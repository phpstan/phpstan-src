<?php declare(strict_types=1); // lint >= 8.4

namespace HookedPropertiesInClass;

class Person
{
	public string $name {
		get => $this->name;
		set => $this->name = $value;
	}
}
