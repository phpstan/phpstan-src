<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Testing\PHPStanTestCaseTrait;

abstract class BenchCase
{

	use PHPStanTestCaseTrait;

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../conf/bleedingEdge.neon',
		];
	}

	/**
	 * @param string[]|null $allAnalysedFiles
	 * @return list<Error>
	 */
	protected function runAnalyse(string $file, ?array $allAnalysedFiles = null): array
	{
		$file = self::getFileHelper()->normalizePath($file);

		$analyser = self::getContainer()->getByType(Analyser::class);
		$finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class);
		return $finalizer->finalize(
			$analyser->analyse([$file], null, null, true, $allAnalysedFiles),
			false,
			true,
		)->getErrors();
	}

}
