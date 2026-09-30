<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PHPStan\Analyser\ValueDependencyCollectorTest\TestValueExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use function array_merge;

final class ValueDependencyCollectorTest extends PHPStanTestCase
{

	public function testDedupAndDependents(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$extension = self::getContainer()->getByType(TestValueExtension::class);
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$analysedFileScope = $scopeFactory->create(ScopeContext::create('/project/src/Analysed.php'));
		$otherFileScope = $scopeFactory->create(ScopeContext::create('/project/src/Other.php'));

		$collector->startFile('/project/src/Analysed.php');
		$collector->record(TestValueExtension::class, 'a', $analysedFileScope, true);
		// the same value again - from another rule or extension - is the same dependency
		$collector->record(TestValueExtension::class, 'a', $analysedFileScope, true);
		$collector->record(TestValueExtension::class, 'b', $otherFileScope, false);

		$a = ValueDependencyCollector::getId(TestValueExtension::class, 'a');
		$b = ValueDependencyCollector::getId(TestValueExtension::class, 'b');
		$this->assertSame([
			'values' => [
				$a => [TestValueExtension::class, 'a', 'value of a'],
				$b => [TestValueExtension::class, 'b', 'value of b'],
			],
			'dependents' => [
				'/project/src/Analysed.php' => [
					'analysis' => [$a, $b],
					'declarations' => [],
				],
				'/project/src/Other.php' => [
					'analysis' => [],
					'declarations' => [$b],
				],
			],
		], $collector->finishFile());
		$this->assertSame(2, $extension->calls);
	}

	public function testNothingOutsideOfAnalysedFile(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('/project/src/Analysed.php'));

		$collector->record(TestValueExtension::class, 'a', $scope, true);
		$collector->startFile('/project/src/Analysed.php');

		$this->assertSame([
			'values' => [],
			'dependents' => [
				'/project/src/Analysed.php' => [
					'analysis' => [],
					'declarations' => [],
				],
			],
		], $collector->finishFile());
	}

	public function testUnregisteredExtension(): void
	{
		$collector = self::getContainer()->getByType(ValueDependencyCollector::class);
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('/project/src/Analysed.php'));

		$collector->startFile('/project/src/Analysed.php');
		try {
			$this->expectException(ShouldNotHappenException::class);
			$this->expectExceptionMessage('stdClass is not registered as a result cache value extension. Register it with the phpstan.resultCacheValueExtension service tag.');
			// @phpstan-ignore argument.type (not an extension on purpose)
			$collector->record('stdClass', 'a', $scope, true);
		} finally {
			$collector->finishFile();
		}
	}

	public function testMerge(): void
	{
		$this->assertSame([
			'values' => [
				'x' => ['E', 'x', 'first'],
				'y' => ['E', 'y', 'y'],
			],
			'dependents' => [
				'/a.php' => ['analysis' => ['x', 'y'], 'declarations' => []],
				'/b.php' => ['analysis' => [], 'declarations' => ['x']],
			],
		], ValueDependencyCollector::merge([
			'values' => ['x' => ['E', 'x', 'first']],
			'dependents' => ['/a.php' => ['analysis' => ['x'], 'declarations' => []]],
		], [
			'values' => ['x' => ['E', 'x', 'second'], 'y' => ['E', 'y', 'y']],
			'dependents' => [
				'/a.php' => ['analysis' => ['x', 'y'], 'declarations' => []],
				'/b.php' => ['analysis' => [], 'declarations' => ['x']],
			],
		]));
	}

	#[Override]
	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/value-dependency-collector.neon',
		]);
	}

}
