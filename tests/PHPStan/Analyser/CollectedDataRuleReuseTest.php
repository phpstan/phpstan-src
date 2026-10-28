<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Analyser\CollectedDataReuse\DescribedClassRule;
use PHPStan\Analyser\CollectedDataReuse\EarlyReturnRule;
use PHPStan\Analyser\CollectedDataReuse\FirstCollector;
use PHPStan\Analyser\CollectedDataReuse\NoCollectedDataRule;
use PHPStan\Analyser\CollectedDataReuse\OnlyFilesRule;
use PHPStan\Analyser\CollectedDataReuse\SecondCollector;
use PHPStan\Analyser\CollectedDataReuse\ThrowingRule;
use PHPStan\Analyser\CollectedDataReuse\TrackingRule;
use PHPStan\Analyser\CollectedDataReuse\UnregisteredValueRule;
use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\Collectors\CollectedData;
use PHPStan\File\FileHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Testing\PHPStanTestCase;
use function array_map;
use function clearstatcache;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * @phpstan-import-type CollectorData from CollectedData
 */
class CollectedDataRuleReuseTest extends PHPStanTestCase
{

	// the errors have to point at files that exist
	private const A = __DIR__ . '/CollectedDataReuse/FirstCollector.php';
	private const B = __DIR__ . '/CollectedDataReuse/SecondCollector.php';
	private const C = __DIR__ . '/CollectedDataReuse/EarlyReturnRule.php';

	public function testRuleRunsAgainOnlyWhenWhatItAskedForChanged(): void
	{
		$rule = new EarlyReturnRule();

		// FirstCollector collected nothing, so the rule returns before it asks for SecondCollector
		$result = $this->finalize([$rule], [
			self::B => [SecondCollector::class => ['b1']],
		], false, []);
		$this->assertSame(1, $rule->runs);
		$this->assertSame([], $this->getMessages($result));

		// SecondCollector changed, but the rule did not ask for it
		$result = $this->finalize([$rule], [
			self::B => [SecondCollector::class => ['b2']],
		], false, $result->getCollectedDataRuleResults());
		$this->assertSame(1, $rule->runs);
		$this->assertSame(1, $result->getReusedRulesCount());

		// FirstCollector changed, so the rule runs and now asks for both
		$result = $this->finalize([$rule], [
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b2']],
		], false, $result->getCollectedDataRuleResults());
		$this->assertSame(2, $rule->runs);
		$this->assertSame(['Second collected b2.'], $this->getMessages($result));

		// SecondCollector changed again, and this time the rule asked for it
		$result = $this->finalize([$rule], [
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b3']],
		], false, $result->getCollectedDataRuleResults());
		$this->assertSame(3, $rule->runs);
		$this->assertSame(['Second collected b3.'], $this->getMessages($result));

		// nothing changed: the errors come from the previous run
		$result = $this->finalize([$rule], [
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b3']],
		], false, $result->getCollectedDataRuleResults());
		$this->assertSame(3, $rule->runs);
		$this->assertSame(1, $result->getReusedRulesCount());
		$this->assertSame(['Second collected b3.'], $this->getMessages($result));
	}

	public function testOrderOfFilesDoesNotMatter(): void
	{
		$rule = new EarlyReturnRule();
		$result = $this->finalize([$rule], [
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b1']],
			self::C => [SecondCollector::class => ['c1']],
		], false, []);

		// a merged result cache lists the files in another order than a full analysis does
		$result = $this->finalize([$rule], [
			self::C => [SecondCollector::class => ['c1']],
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b1']],
		], false, $result->getCollectedDataRuleResults());
		$this->assertSame(1, $rule->runs);
		$this->assertSame(1, $result->getReusedRulesCount());
	}

	public function testOnlyFilesAnalysis(): void
	{
		$onlyFilesRule = new OnlyFilesRule();
		$earlyReturnRule = new EarlyReturnRule();
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
		];

		$result = $this->finalize([$onlyFilesRule, $earlyReturnRule], $collectedData, false, []);
		$this->assertSame(['Full analysis.'], $this->getMessages($result));

		// only the rule that asked for isOnlyFilesAnalysis() runs again
		$result = $this->finalize([$onlyFilesRule, $earlyReturnRule], $collectedData, true, $result->getCollectedDataRuleResults());
		$this->assertSame(2, $onlyFilesRule->runs);
		$this->assertSame(1, $earlyReturnRule->runs);
		$this->assertSame([], $this->getMessages($result));
	}

	public function testRuleThatDoesNotAskForCollectedDataRunsEveryTime(): void
	{
		$file = sys_get_temp_dir() . '/phpstan-collected-data-reuse-' . uniqid() . '.txt';
		file_put_contents($file, 'one');
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
		];

		// tracking a value is not enough, the rule has to ask for collected data
		foreach ([false, true] as $track) {
			$rule = new NoCollectedDataRule($file, $track);
			$result = $this->finalize([$rule], $collectedData, false, []);
			$this->assertSame([], $result->getCollectedDataRuleResults());

			$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
			$this->assertSame(2, $rule->runs);
			$this->assertSame(0, $result->getReusedRulesCount());
			$this->assertSame(['Tool says one.'], $this->getMessages($result));
		}

		unlink($file);
	}

	public function testRuleThatThrowsIsNotReused(): void
	{
		$rule = new ThrowingRule();
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
		];

		$result = $this->finalize([$rule], $collectedData, false, []);
		$this->assertSame([], $result->getCollectedDataRuleResults());

		$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(2, $rule->runs);
		$this->assertSame(['Function notThere not found while trying to analyse it - discovering symbols is probably not configured properly.'], $this->getMessages($result));
	}

	public function testTwoRulesOfTheSameClassKeepTheirOwnResults(): void
	{
		$first = new OnlyFilesRule();
		$second = new EarlyReturnRule();
		$third = new EarlyReturnRule();
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b1']],
		];

		$result = $this->finalize([$first, $second, $third], $collectedData, false, []);
		$this->assertCount(3, $result->getCollectedDataRuleResults());

		$result = $this->finalize([$first, $second, $third], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(3, $result->getReusedRulesCount());
		$this->assertSame(['Full analysis.', 'Second collected b1.', 'Second collected b1.'], $this->getMessages($result));
	}

	public function testRuleRunsAgainWhenATrackedValueChanged(): void
	{
		$directory = sys_get_temp_dir() . '/phpstan-collected-data-reuse-' . uniqid();
		mkdir($directory);
		$file = $directory . '/value.php';
		file_put_contents($file, 'one');
		file_put_contents($directory . '/a.txt', '');
		$valueFile = $directory . '/value.data';
		file_put_contents($valueFile, 'a');

		$rule = new TrackingRule($file, $directory, $valueFile);
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
		];

		$result = $this->finalize([$rule], $collectedData, false, []);
		$this->assertSame(1, $rule->runs);
		$this->assertSame(['File says one, directory has 1 files, value is a.'], $this->getMessages($result));

		// nothing changed
		$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(1, $rule->runs);
		$this->assertSame(1, $result->getReusedRulesCount());
		$this->assertSame(['File says one, directory has 1 files, value is a.'], $this->getMessages($result));

		// the tracked file changed - in size too, FileContentHasher memoizes by mtime and size
		file_put_contents($file, 'three');
		clearstatcache();
		$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(2, $rule->runs);
		$this->assertSame(['File says three, directory has 1 files, value is a.'], $this->getMessages($result));

		// a file appeared in the tracked directory
		file_put_contents($directory . '/b.txt', '');
		$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(3, $rule->runs);
		$this->assertSame(['File says three, directory has 2 files, value is a.'], $this->getMessages($result));

		// a file that does not match the pattern does not count
		file_put_contents($directory . '/c.md', '');
		$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(3, $rule->runs);
		$this->assertSame(1, $result->getReusedRulesCount());

		// the value declared through trackValueDependency() changed
		file_put_contents($valueFile, 'bb');
		clearstatcache();
		$result = $this->finalize([$rule], $collectedData, false, $result->getCollectedDataRuleResults());
		$this->assertSame(4, $rule->runs);
		$this->assertSame(['File says three, directory has 2 files, value is bb.'], $this->getMessages($result));

		unlink($valueFile);
		unlink($directory . '/c.md');
		unlink($directory . '/b.txt');
		unlink($directory . '/a.txt');
		unlink($file);
		rmdir($directory);
	}

	public function testRuleRunsAgainWhenWhatAClassDeclaresChanged(): void
	{
		$file = sys_get_temp_dir() . '/phpstan-collected-data-reuse-' . uniqid() . '.txt';
		file_put_contents($file, 'one');
		$classReflection = self::createReflectionProvider()->getClass(FirstCollector::class);

		$rule = new DescribedClassRule($file);
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
		];

		// a new process each run: an extension describes the class once, before the rule asks for it
		/** @param array<string, CollectedDataRuleResult> $previousResults */
		$finalize = function (array $previousResults) use ($rule, $collectedData, $file, $classReflection): FinalizerResult {
			$valueDependencyCollector = new ValueDependencyCollector(
				self::getContainer()->getExtensionsCollection(ResultCacheValueExtension::class),
				self::getContainer()->getByType(FileHelper::class),
			);
			(new ValueDependencyDeclarationDependencyTracker($valueDependencyCollector))->trackFileDependency($classReflection, $file);

			return $this->finalize([$rule], $collectedData, false, $previousResults, true, $valueDependencyCollector);
		};

		$result = $finalize([]);
		$this->assertSame(1, $rule->runs);
		$this->assertSame(['Class is described by one.'], $this->getMessages($result));

		// nothing changed
		$result = $finalize($result->getCollectedDataRuleResults());
		$this->assertSame(1, $rule->runs);
		$this->assertSame(1, $result->getReusedRulesCount());

		// the file the extension described the class from changed - in size too, FileContentHasher memoizes by mtime and size
		file_put_contents($file, 'three');
		clearstatcache();
		$result = $finalize($result->getCollectedDataRuleResults());
		$this->assertSame(2, $rule->runs);
		$this->assertSame(['Class is described by three.'], $this->getMessages($result));

		unlink($file);
	}

	public function testEveryRuleRunsWithoutTheFeatureToggle(): void
	{
		$rule = new EarlyReturnRule();
		$collectedData = [
			self::A => [FirstCollector::class => ['a1']],
			self::B => [SecondCollector::class => ['b1']],
		];

		$result = $this->finalize([$rule], $collectedData, false, [], false);
		$this->assertSame([], $result->getCollectedDataRuleResults());

		$previousResults = $this->finalize([$rule], $collectedData, false, [])->getCollectedDataRuleResults();
		$this->assertCount(1, $previousResults);
		$this->assertSame(2, $rule->runs);

		$result = $this->finalize([$rule], $collectedData, false, $previousResults, false);
		$this->assertSame(3, $rule->runs);
		$this->assertSame(0, $result->getReusedRulesCount());
		$this->assertSame(['Second collected b1.'], $this->getMessages($result));

		// without the toggle, the declared values are not asked for, as before
		$result = $this->finalize([new UnregisteredValueRule()], $collectedData, false, [], false);
		$this->assertSame(['Unregistered value.'], $this->getMessages($result));
	}

	/**
	 * @param list<Rule<CollectedDataNode>> $rules
	 * @param CollectorData $collectedData
	 * @param array<string, CollectedDataRuleResult> $previousResults
	 */
	private function finalize(array $rules, array $collectedData, bool $onlyFiles, array $previousResults, bool $reuse = true, ?ValueDependencyCollector $valueDependencyCollector = null): FinalizerResult
	{
		$finalizer = new AnalyserResultFinalizer(
			new DirectRegistry($rules),
			self::getContainer()->getExtensionsCollection(IgnoreErrorExtension::class),
			self::getContainer()->getByType(RuleErrorTransformer::class),
			self::createScopeFactory(self::createReflectionProvider(), self::getContainer()->getService('typeSpecifier')),
			new LocalIgnoresProcessor(),
			false,
			$valueDependencyCollector ?? self::getContainer()->getByType(ValueDependencyCollector::class),
			$reuse,
		);

		return $finalizer->finalize(new AnalyserResult(
			unorderedErrors: [],
			filteredPhpErrors: [],
			allPhpErrors: [],
			locallyIgnoredErrors: [],
			linesToIgnore: [],
			unmatchedLineIgnores: [],
			internalErrors: [],
			collectedData: $collectedData,
			dependencies: [],
			usedTraitDependencies: [],
			valueDependencies: null,
			packageDependencies: [],
			exportedNodes: [],
			reachedInternalErrorsCountLimit: false,
			peakMemoryUsageBytes: 0,
			processedFiles: [],
		), $onlyFiles, false, null, null, $previousResults);
	}

	/**
	 * @return list<string>
	 */
	private function getMessages(FinalizerResult $result): array
	{
		return array_map(static fn (Error $error): string => $error->getMessage(), $result->getCollectorErrors());
	}

}
