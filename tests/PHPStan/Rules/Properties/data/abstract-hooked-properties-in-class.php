<?php declare(strict_types=1);

namespace NonAbstractHookedPropertiesInAbstractClass;

class AbstractPerson
{
	public abstract string $name { get; set; }

	public abstract string $lastName { get; set; }
}
