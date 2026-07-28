<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Testing\PHPStanTestCaseTrait;
use function fclose;
use function fgets;
use function fopen;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function version_compare;
use const PHP_VERSION;

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
	 * Data files that cannot be parsed on the current PHP version are not worth
	 * benchmarking - the analysis stops at the syntax error.
	 *
	 * Copy of TypeInferenceTestCase::isFileLintSkipped(), originally from
	 * https://github.com/php-parallel-lint/PHP-Parallel-Lint/blob/0c2706086ac36dce31967cb36062ff8915fe03f7/bin/skip-linting.php
	 *
	 * Copyright (c) 2012, Jakub Onderka
	 */
	protected static function isFileLintSkipped(string $file): bool
	{
		$f = @fopen($file, 'r');
		if ($f !== false) {
			$firstLine = fgets($f);
			if ($firstLine === false) {
				return false;
			}

			// ignore shebang line
			if (str_starts_with($firstLine, '#!')) {
				$firstLine = fgets($f);
				if ($firstLine === false) {
					return false;
				}
			}

			@fclose($f);

			if (preg_match('~<?php\\s*\\/\\/\s*lint\s*([^\d\s]+)\s*([^\s]+)\s*~i', $firstLine, $m) === 1) {
				return version_compare(PHP_VERSION, $m[2], $m[1]) === false;
			} elseif (str_contains($firstLine, 'lint')) {
				throw new LogicException(sprintf("'// lint' comment must immediately follow the php starting tag in %s on line 1", $file));
			}
		}

		return false;
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
