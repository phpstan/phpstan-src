<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use function array_map;
use function array_merge;
use function array_unique;
use function error_reporting;
use const E_ALL;
use const PHP_VERSION_ID;

#[CoversNothing]
class Bug13813IntegrationTest extends PHPStanTestCase
{

	public function testBug13813(): void
	{
		$oldReporting = error_reporting();

		try {
			error_reporting(E_ALL);
			$analyzerResult = $this->runAnalyse([
				__DIR__ . '/data/bug-13813.php',
				__DIR__ . '/Bug13813Rule.php',
			]);
		} finally {
			error_reporting($oldReporting);
		}
		self::assertCount(2, $analyzerResult->getAllPhpErrors());
		self::assertCount(2, $analyzerResult->getFilteredPhpErrors());

		if (PHP_VERSION_ID >= 80000) {
			self::assertSame(
				'Warning: Undefined variable $x',
				$analyzerResult->getAllPhpErrors()[0]->getMessage(),
			);
			self::assertSame(
				'Warning: Undefined variable $x',
				$analyzerResult->getAllPhpErrors()[1]->getMessage(),
			);
		} else {
			self::assertSame(
				'Notice: Undefined variable: x',
				$analyzerResult->getAllPhpErrors()[0]->getMessage(),
			);
			self::assertSame(
				'Notice: Undefined variable: x',
				$analyzerResult->getAllPhpErrors()[1]->getMessage(),
			);
		}
	}

	/**
	 * @param string[] $files
	 */
	private function runAnalyse(array $files): AnalyserResult
	{
		$files = array_map(fn (string $file): string => $this->getFileHelper()->normalizePath($file), $files);
		/** @var Analyser $analyser */
		$analyser = self::getContainer()->getByType(Analyser::class);

		return $analyser->analyse($files);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_unique(
			array_merge(
				parent::getAdditionalConfigFiles(),
				[
					__DIR__ . '/bug13813.neon',
				],
			),
		);
	}

}
