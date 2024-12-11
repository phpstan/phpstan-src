<?php declare(strict_types=1);

namespace HookedPropertiesWithoutBodiesInClass;

class AbstractPerson
{
	public string $name { get; set; }

	public string $lastName { get; set; }
}

class PromotedHookedPropertyWithoutVisibility
{

	public function __construct(mixed $test { get; })
	{

	}

}
