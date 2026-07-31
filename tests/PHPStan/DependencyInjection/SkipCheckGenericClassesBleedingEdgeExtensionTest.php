<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;

class SkipCheckGenericClassesBleedingEdgeExtensionTest extends PHPStanTestCase
{

	public function testClassesMarkedInStubsAreCheckedWithBleedingEdge(): void
	{
		$featureToggles = self::getContainer()->getParameter('featureToggles');
		$this->assertSame([], $featureToggles['skipCheckGenericClasses']);
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
		];
	}

}
