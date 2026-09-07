<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;

class ClosureAnalysisTest extends TypeInferenceTestCase
{

	/** @return iterable<array{string}> */
	public static function dataIntrinsicClosures(): iterable
	{
		yield [__DIR__ . '/data/immediately-invoked-closure-analysis.php'];
		yield [__DIR__ . '/data/array-map-closure-analysis.php'];
	}

	#[DataProvider('dataIntrinsicClosures')]
	public function testIntrinsicClosuresReuseBodyAnalysis(string $file): void
	{
		TemplateArgumentStats::reset();
		TemplateArgumentStats::$enabled = true;
		try {
			$asserts = self::gatherAssertTypes($file);
			$this->assertSame(0, TemplateArgumentStats::getCounters()['closureTypeBodyWalks']);
			foreach ($asserts as $args) {
				$this->assertFileAsserts(...$args);
			}
		} finally {
			TemplateArgumentStats::$enabled = false;
		}
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [__DIR__ . '/../../../../conf/bleedingEdge.neon']);
	}

}
