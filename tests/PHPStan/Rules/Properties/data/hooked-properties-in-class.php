<?php declare(strict_types=1);

namespace HookedPropertiesInClass;

class Person
{
	public string $name {
		get => $this->name;
		set => $this->name = $value;
	}
}
