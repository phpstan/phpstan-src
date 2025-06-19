<?php declare(strict_types = 1);

namespace PHPStan\Levels;

use PHPStan\Testing\LevelsTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('levels')]
class StubsIntegrationTest extends LevelsTestCase
{

	public static function dataTopics(): array
	{
		require_once __DIR__ . '/data/stubs-functions.php';

		return [
			['stubs-functions'],
			['stubs-methods'],
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
		return __DIR__ . '/stubs.neon';
	}

}
