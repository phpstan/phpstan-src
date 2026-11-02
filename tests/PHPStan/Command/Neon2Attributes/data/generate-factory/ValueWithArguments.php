<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\GenerateFactory;

final class ValueWithArguments
{

	public function __construct(
		public string $name,
		public string $tmpDir,
	)
	{
	}

}
