<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Generator;
use PhpParser\Node;
use PHPStan\Analyser\Ignore\LineIgnoreFixer;
use PHPStan\Analyser\Ignore\UnmatchedIgnoreFixer;
use PHPStan\Analyser\ResultCache\CollectedDataReader;
use PHPStan\Analyser\ResultCache\CollectorLocations;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\Analyser\ResultCache\UnreadableCollectedDataException;
use PHPStan\DependencyInjection\DirectExtensionsCollection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\Traits\TraitDeclarationCollector;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use Throwable;
use function array_map;
use function count;
use function get_class;
use function md5;
use function sprintf;

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

	public function testUnreadableCollectedDataIsAnInternalErrorThatIsNotABug(): void
	{
		$result = $this->createFinalizer([$this->createReportingRule(), $this->createIteratingRule(false)])->finalize(
			$this->createAnalyserResultWithUnreadableCache(),
			false,
			false,
		)->getAnalyserResult();

		$this->assertSame([], $result->getErrors(), 'no rule reports from partial data');
		$this->assertCount(1, $result->getInternalErrors());
		$this->assertSame('The cache file is damaged.', $result->getInternalErrors()[0]->getMessage());
		$this->assertFalse($result->getInternalErrors()[0]->shouldReportBug());
	}

	public function testUnreadableCollectedDataIsReportedWhenTheRuleCatchesTheError(): void
	{
		$result = $this->createFinalizer([$this->createIteratingRule(true)])->finalize(
			$this->createAnalyserResultWithUnreadableCache(),
			false,
			false,
		)->getAnalyserResult();

		$this->assertSame([], $result->getErrors());
		$this->assertCount(1, $result->getInternalErrors());
		$this->assertSame('The cache file is damaged.', $result->getInternalErrors()[0]->getMessage());
		$this->assertFalse($result->getInternalErrors()[0]->shouldReportBug());
	}

	public function testUnreadableCollectedDataKeepsCrashesOfEarlierRules(): void
	{
		$result = $this->createFinalizer([$this->createThrowingRule(), $this->createIteratingRule(false)])->finalize(
			$this->createAnalyserResultWithUnreadableCache(),
			false,
			false,
		)->getAnalyserResult();

		$this->assertSame([], $result->getErrors());
		$this->assertSame(['Failed.', 'The cache file is damaged.'], array_map(static fn (InternalError $error): string => $error->getMessage(), $result->getInternalErrors()));
	}

	public function testUnreadableCollectedDataIsRethrownInDebugMode(): void
	{
		$this->expectException(UnreadableCollectedDataException::class);
		$this->createFinalizer([$this->createIteratingRule(true)])->finalize(
			$this->createAnalyserResultWithUnreadableCache(),
			false,
			true,
		);
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
			self::getContainer()->getByType(UnmatchedIgnoreFixer::class),
			self::getContainer()->getByType(LineIgnoreFixer::class),
			false,
			self::getContainer()->getByType(ValueDependencyCollector::class),
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
			collectedData: LazyCollectedData::fromArray([__FILE__ => [TraitDeclarationCollector::class => ['data']]]),
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
	 * The cache holds two files; reading the second one fails.
	 */
	private function createAnalyserResultWithUnreadableCache(): AnalyserResult
	{
		$reader = new class implements CollectedDataReader {

			public function read(array $index): array
			{
				throw new UnreadableCollectedDataException('The cache file is damaged.');
			}

			public function locateCollectors(array $index): CollectorLocations
			{
				$positions = [];
				$hashes = [];
				foreach ($index as $file => $position) {
					$positions[TraitDeclarationCollector::class][$file] = $position;
					$hashes[TraitDeclarationCollector::class][$file] = md5($file);
				}

				return new CollectorLocations('signature', $positions, $hashes);
			}

			/**
			 * @return Generator<string, list<mixed>>
			 */
			public function readCollector(string $collectorType, array $positions, string $signature): Generator
			{
				yield 'a.php' => [['Trait', 1]];

				throw new UnreadableCollectedDataException('The cache file is damaged.');
			}

		};

		return new AnalyserResult(
			unorderedErrors: [],
			filteredPhpErrors: [],
			allPhpErrors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			internalErrors: [],
			collectedData: new LazyCollectedData(['a.php' => [0, 1], 'b.php' => [1, 1]], $reader, []),
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
	private function createIteratingRule(bool $catchesErrors): Rule
	{
		return new /** @implements Rule<CollectedDataNode> */class ($catchesErrors) implements Rule {

			public function __construct(private bool $catchesErrors)
			{
			}

			public function getNodeType(): string
			{
				return CollectedDataNode::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				$files = [];
				try {
					foreach ($node->iterate(TraitDeclarationCollector::class) as $file => $values) {
						$files[$file] = count($values);
					}
				} catch (Throwable $e) {
					if (!$this->catchesErrors) {
						throw $e;
					}
				}

				return [RuleErrorBuilder::message(sprintf('Saw %d files.', count($files)))->identifier('tests.saw')->file(__FILE__)->line(1)->build()];
			}

		};
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
