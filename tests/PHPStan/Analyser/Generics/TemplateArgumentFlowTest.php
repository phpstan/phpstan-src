<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;

class TemplateArgumentFlowTest extends TypeInferenceTestCase
{

	/** @return iterable<mixed[]> */
	public static function dataConstraintsSurviveControlFlow(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/constraint-flow.php');
	}

	#[DataProvider('dataConstraintsSurviveControlFlow')]
	public function testConstraintsSurviveControlFlow(string $assertType, string $file, mixed ...$args): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [__DIR__ . '/../../../../conf/bleedingEdge.neon']);
	}

}
