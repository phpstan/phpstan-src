<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Testing\TypeInferenceTestCase;
use function array_merge;

class TemplateArgumentFlowWithoutPollutionTest extends TypeInferenceTestCase
{

	public function testConstraintsSurviveControlFlow(): void
	{
		foreach (self::gatherAssertTypes(__DIR__ . '/data/constraint-flow.php') as $args) {
			$this->assertFileAsserts(...$args);
		}
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
			__DIR__ . '/without-foreach-pollution.neon',
		]);
	}

}
