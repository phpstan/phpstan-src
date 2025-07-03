<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;

class IgnoreErrorsTest extends PHPStanTestCase
{

	public function testIgnoreErrors(): void
	{
		$this->assertCount(16, self::getContainer()->getParameter('ignoreErrors'));
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/ignoreErrors.neon',
		];
	}

}
