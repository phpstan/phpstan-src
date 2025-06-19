<?php declare(strict_types = 1);

namespace PHPStan\Levels;

use PHPStan\Testing\LevelsTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('levels')]
class StubValidatorIntegrationTest extends LevelsTestCase
{

	public static function dataTopics(): array
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
