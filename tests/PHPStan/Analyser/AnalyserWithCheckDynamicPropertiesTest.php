<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;
use function array_merge;
use function array_unique;

class AnalyserWithCheckDynamicPropertiesTest extends PHPStanTestCase
{

	public function testBug13529(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-13529.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Access to an undefined property object::$bar.', $errors[0]->getMessage());
		$this->assertSame(8, $errors[0]->getLine());
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
					__DIR__ . '/checkDynamicProperties.neon',
				],
			),
		);
	}

}
