<?php declare(strict_types = 1);

namespace PHPStan\Levels;

use PHPStan\Testing\LevelsTestCase;
use const PHP_VERSION_ID;

/**
 * @group levels
 */
class LevelsIntegrationTest extends LevelsTestCase
{

	public function dataTopics(): array
	{
		$topics = [
			['returnTypes'],
			['acceptTypes'],
			['methodCalls'],
			['propertyAccesses'],
			['constantAccesses'],
			['variables'],
			['callableCalls'],
			['callableVariance'],
			['arrayDimFetches'],
			['clone'],
			['iterable'],
			['binaryOps'],
			['comparison'],
			['throwValues'],
			['casts'],
			['unreachable'],
			['echo_'],
			['print_'],
			['stringOffsetAccess'],
			['object'],
			['encapsedString'],
			['missingReturn'],
			['arrayAccess'],
			['typehints'],
			['coalesce'],
			['arrayDestructuring'],
			['listType'],
		];
		if (PHP_VERSION_ID >= 80300) {
			$topics[] = ['constantAccesses83'];
		}

		return $topics;
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
		return __DIR__ . '/dynamicConstantNames.neon';
	}

}
