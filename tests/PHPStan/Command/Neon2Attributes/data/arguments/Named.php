<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Arguments;

final class Named
{

	public function __construct(
		private bool $treatPhpDocTypesAsCertain,
		private bool $tip,
		private Positional $other,
		private string $literal,
		private string $concatenated,
	)
	{
	}

}
