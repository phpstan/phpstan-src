<?php declare(strict_types=1);

namespace NonAbstractHookedPropertiesInAbstractClass;

abstract class AbstractPerson
{
	public string $name { get; set; }

	public string $lastName { get; set; }
}
