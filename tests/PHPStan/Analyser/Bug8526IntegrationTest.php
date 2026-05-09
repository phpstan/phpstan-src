<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use function array_merge;
use function array_unique;

#[CoversNothing]
class Bug8526IntegrationTest extends PHPStanTestCase
{

	public function testBug8526(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-8526.php');
		$this->assertNoErrors($errors);
	}

	/**
	 * @return list<Error>
	 */
	private function runAnalyse(string $file): array
	{
		$file = $this->getFileHelper()->normalizePath($file);

		$analyser = self::getContainer()->getByType(Analyser::class);
		$finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class);
		$errors = $finalizer->finalize(
			$analyser->analyse([$file], null, null, true),
			false,
			true,
		)->getErrors();
		foreach ($errors as $error) {
			$this->assertSame($file, $error->getFilePath());
		}

		return $errors;
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_unique(
			array_merge(
				parent::getAdditionalConfigFiles(),
				[
					__DIR__ . '/bug-8526.neon',
				],
			),
		);
	}

}
