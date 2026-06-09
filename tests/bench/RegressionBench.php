<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PhpBench\Attributes as Bench;
use Symfony\Component\Finder\Finder;
use function array_first;

#[Bench\Revs(revs: 1)]
#[Bench\Iterations(iterations: 5)]
#[Bench\Warmup(revs: 1)]
#[Bench\RetryThreshold(retryThreshold: 10.0)]
#[Bench\Assert(expression: '
    (mode(baseline.time.avg) < 100 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 50%)
    or (mode(baseline.time.avg) >= 100 milliseconds and mode(baseline.time.avg) < 500 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 25%)
    or (mode(baseline.time.avg) >= 500 milliseconds and mode(baseline.time.avg) < 2000 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 20%)
    or (mode(baseline.time.avg) >= 2000 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 10%)
')]
class RegressionBench extends BenchCase
{

	/**
	 * @param array{string} $params
	 */
	#[Bench\ParamProviders(['provideFiles'])]
	public function benchRunAnalyse(array $params): void
	{
		$this->runAnalyse($params[0]);
	}

	/**
	 * @return iterable<array{string}>
	 */
	public function provideFiles(): iterable
	{
		$arr = self::findTestDataFilesFromDirectory(__DIR__ . '/data');
		yield array_first($arr);
	}

	private static function findTestDataFilesFromDirectory(string $directory): array
	{
		$finder = new Finder();
		$finder->followLinks();
		$finder->sortByName(true);
		$files = [];
		foreach ($finder->files()->name('*.php')->in($directory) as $fileInfo) {
			$files[$fileInfo->getBasename()] = [$fileInfo->getPathname()];
		}

		return $files;
	}

}
