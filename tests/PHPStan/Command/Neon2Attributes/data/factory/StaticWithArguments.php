<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Factory;

final class StaticWithArguments
{

	public static function create(string $directory): self
	{
		return new self();
	}

}
