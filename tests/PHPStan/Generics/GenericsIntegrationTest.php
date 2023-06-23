<?php declare(strict_types = 1);

namespace PHPStan\Generics;

use PHPStan\Testing\LevelsTestCase;

/**
 * @group levels
 */
class GenericsIntegrationTest extends LevelsTestCase
{

	public function dataTopics(): array
	{
		return [
			['functions'],
			['invalidReturn'],
			['pick'],
			['varyingAcceptor'],
			['classes'],
			['variance'],
			['typeProjections'],
			['bug2574'],
			['bug2577'],
			['bug2620'],
			['bug2622'],
			['bug2627'],
			['bug6210'],
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
		return __DIR__ . '/generics.neon';
	}

}
