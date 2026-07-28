<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Symfony\Component\Finder\Finder;

/**
 * PHPBench annotations are used instead of attributes so that the benchmark
 * also runs on PHP 7.4 with the downgraded source code, where attributes
 * are just comments.
 *
 * The assertion expression has to stay on a single line - an annotation value
 * cannot span multiple lines. Annotation names must not be mentioned anywhere
 * else in this docblock either, the annotation reader tries to parse them.
 *
 * @Revs(1)
 * @Iterations(5)
 * @Warmup(1)
 * @RetryThreshold(10.0)
 * @Assert("(mode(baseline.time.avg) < 100 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 50%) or (mode(baseline.time.avg) >= 100 milliseconds and mode(baseline.time.avg) < 500 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 25%) or (mode(baseline.time.avg) >= 500 milliseconds and mode(baseline.time.avg) < 2000 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 20%) or (mode(baseline.time.avg) >= 2000 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 10%)")
 */
class RegressionBench extends BenchCase
{

	/**
	 * @ParamProviders({"provideFiles"})
	 *
	 * @param array{string} $params
	 */
	public function benchRunAnalyse(array $params): void
	{
		$this->runAnalyse($params[0]);
	}

	/**
	 * @return iterable<array{string}>
	 */
	public function provideFiles(): iterable
	{
		yield from self::findTestDataFilesFromDirectory(__DIR__ . '/data');
	}

	private static function findTestDataFilesFromDirectory(string $directory): array
	{
		$finder = new Finder();
		$finder->followLinks();
		$finder->sortByName(true);
		$files = [];
		foreach ($finder->files()->name('*.php')->in($directory) as $fileInfo) {
			if (self::isFileLintSkipped($fileInfo->getPathname())) {
				continue;
			}

			$files[$fileInfo->getBasename()] = [$fileInfo->getPathname()];
		}

		return $files;
	}

}
