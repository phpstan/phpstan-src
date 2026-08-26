<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures;

final class ConvFixtureService
{

	public function __construct(
		private string $tmpDir,
		private string $level,
	)
	{
	}

	public function describe(): string
	{
		return $this->tmpDir . $this->level;
	}

}
