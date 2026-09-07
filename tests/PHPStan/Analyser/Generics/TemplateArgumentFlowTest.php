<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;
use const PHP_VERSION_ID;

class TemplateArgumentFlowTest extends TypeInferenceTestCase
{

	/** @return iterable<mixed[]> */
	public static function dataConstraintsSurviveControlFlow(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/constraint-flow.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/joint-inference.php');
		if (PHP_VERSION_ID < 80000) {
			return;
		}
		yield from self::gatherAssertTypes(__DIR__ . '/../../Rules/Functions/data/joint-inference-named.php');
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
