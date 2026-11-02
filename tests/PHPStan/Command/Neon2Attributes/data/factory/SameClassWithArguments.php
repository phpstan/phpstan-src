<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Factory;

final class SameClassWithArguments
{

	public function __construct(
		private string $directory,
	)
	{
	}

}
