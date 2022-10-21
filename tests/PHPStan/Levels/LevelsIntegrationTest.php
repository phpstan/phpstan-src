<?php declare(strict_types = 1);

namespace PHPStan\Levels;

use PHPStan\Testing\LevelsTestCase;

/**
 * @group levels
 */
class LevelsIntegrationTest extends LevelsTestCase
{

	public function dataTopics(): array
	{
		return [
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
