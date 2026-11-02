<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Arguments;

abstract class ParentWithConstructor
{

	public function __construct(private bool $flag)
	{
	}

}
