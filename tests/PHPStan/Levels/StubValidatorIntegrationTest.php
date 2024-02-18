<?php declare(strict_types = 1);

namespace PHPStan\Levels;

use PHPStan\Testing\LevelsTestCase;

/**
 * @group levels
 */
class StubValidatorIntegrationTest extends LevelsTestCase
{

	public function dataTopics(): array
	{
		return [
			['stubValidator'],
		];
	}

	public function getDataPath(): string
	{
		return __DIR__ . '/data';
	}

	public function getPhpStanExecutablePath(): string
	{
		return __DIR__ . '/../../../bin/phpstan';
	}

	public function getPhpStanConfigPath(): string
	{
		return __DIR__ . '/stubValidator.neon';
	}

	/**
	 * @return string[]
	 */
	public function getAdditionalAnalysedFiles(): array
	{
		return [
			__DIR__ . '/data/stubValidator/stubs.php',
		];
	}

}
