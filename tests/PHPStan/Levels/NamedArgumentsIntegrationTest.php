<?php declare(strict_types = 1);

namespace PHPStan\Levels;

/**
 * @group exec
 */
class NamedArgumentsIntegrationTest extends \PHPStan\Testing\LevelsTestCase
{

	public function dataTopics(): array
	{
		return [
			['namedArguments'],
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
		return __DIR__ . '/staticReflection.neon';
	}

	protected function shouldAutoloadAnalysedFile(): bool
	{
		return false;
	}

}
