<?php declare(strict_types=1); // lint >= 8.4

namespace AbstractHookedPropertiesInClass;

class AbstractPerson
{
	public abstract string $name { get; set; }

	public abstract string $lastName { get; set; }
}
