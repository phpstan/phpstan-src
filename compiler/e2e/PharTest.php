<?php declare(strict_types = 1);

class PharTest extends \PHPStan\Testing\LevelsTestCase
{

	public function dataTopics(): array
	{
		return [
			['strictRulesExtension'],
		];
	}

	public function getDataPath(): string
	{
		return __DIR__ . '/data';
	}

	public function getPhpStanExecutablePath(): string
	{
		return __DIR__ . '/vendor/phpstan/phpstan/phpstan.phar';
	}

	public function getPhpStanConfigPath(): ?string
	{
		return __DIR__ . '/phpstan.neon';
	}

}
