<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node;
use PHPStan\Analyser\RecordingNodeCallback;
use PHPStan\Analyser\StatementContext;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;

class AnalysisContextTest extends TypeInferenceTestCase
{

	/** @return iterable<array{bool}> */
	public static function dataResolution(): iterable
	{
		yield [true];
		yield [false];
	}

	#[DataProvider('dataResolution')]
	public function testRecordingDoesNotDetermineInference(bool $resolveTemplateArguments): void
	{
		$file = __DIR__ . '/data/explicit-analysis-context.php';
		$resolver = self::createNodeScopeResolver();
		$resolver->setAnalysedFiles([$file]);
		$resolver->resetPerFileAnalysisState();
		$recording = new RecordingNodeCallback();
		TemplateArgumentStats::reset();
		TemplateArgumentStats::$enabled = true;
		try {
			$resolver->processStmtNodes(
				new Node\Stmt\Nop(),
				self::getParser()->parseFile($file),
				self::createScope($file),
				$recording,
				StatementContext::createTopLevel($resolveTemplateArguments),
			);
			$this->assertSame($resolveTemplateArguments ? 1 : 0, TemplateArgumentStats::getCounters()['bodiesWithSites']);
		} finally {
			TemplateArgumentStats::$enabled = false;
		}

		if (!$resolveTemplateArguments) {
			return;
		}

		$returnTypes = [];
		foreach ($recording->getPairs() as [$node, $scope]) {
			if (!$node instanceof Node\Stmt\Return_) {
				continue;
			}
			$returnTypes[] = $scope->getVariableType('ints');
		}
		$this->assertCount(1, $returnTypes);
		$this->assertTrue((new GenericObjectType('ArrayObject', [new IntegerType(), new IntegerType()]))->equals($returnTypes[0]));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [__DIR__ . '/../../../../conf/bleedingEdge.neon']);
	}

}
