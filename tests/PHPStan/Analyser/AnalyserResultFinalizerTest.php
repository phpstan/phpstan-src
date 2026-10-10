<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\DirectExtensionsCollection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\Traits\TraitDeclarationCollector;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use function get_class;

class AnalyserResultFinalizerTest extends PHPStanTestCase
{

	public function testRuleCallbacksRunAroundEveryCollectedDataRule(): void
	{
		$reportingRule = $this->createReportingRule();
		$throwingRule = $this->createThrowingRule();

		$events = [];
		$result = $this->createFinalizer([$reportingRule, $throwingRule])->finalize(
			$this->createAnalyserResult(),
			false,
			false,
			static function (string $ruleClass) use (&$events): void {
				$events[] = 'pre ' . $ruleClass;
			},
			static function () use (&$events): void {
				$events[] = 'post';
			},
		)->getAnalyserResult();

		$this->assertSame([
			'pre ' . get_class($reportingRule),
			'post',
			'pre ' . get_class($throwingRule),
			'post',
		], $events);
		$this->assertCount(1, $result->getErrors());
		$this->assertSame('Reported.', $result->getErrors()[0]->getMessage());
		$this->assertCount(1, $result->getInternalErrors());
		$this->assertSame('Failed.', $result->getInternalErrors()[0]->getMessage());
	}

	public function testPostRuleCallbackRunsWhenDebugRethrows(): void
	{
		$throwingRule = $this->createThrowingRule();

		$events = [];
		try {
			$this->createFinalizer([$throwingRule])->finalize(
				$this->createAnalyserResult(),
				false,
				true,
				static function (string $ruleClass) use (&$events): void {
					$events[] = 'pre ' . $ruleClass;
				},
				static function () use (&$events): void {
					$events[] = 'post';
				},
			);
			$this->fail('The exception should have been rethrown in debug mode.');
		} catch (RuntimeException $e) {
			$this->assertSame('Failed.', $e->getMessage());
		}

		$this->assertSame([
			'pre ' . get_class($throwingRule),
			'post',
		], $events);
	}

	/**
	 * @param list<Rule<CollectedDataNode>> $rules
	 */
	private function createFinalizer(array $rules): AnalyserResultFinalizer
	{
		return new AnalyserResultFinalizer(
			new DirectRuleRegistry($rules),
			new DirectExtensionsCollection([]),
			self::getContainer()->getByType(RuleErrorTransformer::class),
			self::createScopeFactory(
				self::createReflectionProvider(),
				self::getContainer()->getService('typeSpecifier'),
			),
			new LocalIgnoresProcessor(),
			false,
		);
	}

	private function createAnalyserResult(): AnalyserResult
	{
		return new AnalyserResult(
			unorderedErrors: [],
			filteredPhpErrors: [],
			allPhpErrors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			internalErrors: [],
			collectedData: [__FILE__ => [TraitDeclarationCollector::class => ['data']]],
			dependencies: null,
			usedTraitDependencies: null,
			valueDependencies: null,
			packageDependencies: null,
			exportedNodes: [],
			reachedInternalErrorsCountLimit: false,
			peakMemoryUsageBytes: 0,
			processedFiles: [],
		);
	}

	/**
	 * @return Rule<CollectedDataNode>
	 */
	private function createReportingRule(): Rule
	{
		return new /** @implements Rule<CollectedDataNode> */class implements Rule {

			public function getNodeType(): string
			{
				return CollectedDataNode::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				return [RuleErrorBuilder::message('Reported.')->identifier('tests.reported')->file(__FILE__)->line(1)->build()];
			}

		};
	}

	/**
	 * @return Rule<CollectedDataNode>
	 */
	private function createThrowingRule(): Rule
	{
		return new /** @implements Rule<CollectedDataNode> */class implements Rule {

			public function getNodeType(): string
			{
				return CollectedDataNode::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				throw new RuntimeException('Failed.');
			}

		};
	}

}
