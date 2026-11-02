<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Factory;

final class CreatedFactory
{

	public function create(): Created
	{
		return new Created();
	}

	public static function createStatically(): StaticallyCreated
	{
		return new StaticallyCreated();
	}

}
