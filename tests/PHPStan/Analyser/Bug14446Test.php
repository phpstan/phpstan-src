<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileHelper;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class Bug14446Test extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-14446.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataFileAsserts')]
	public function testFileAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-14446.neon',
		];
	}

	public function testRule(): void
	{
		$file = self::getContainer()->getByType(FileHelper::class)->normalizePath(__DIR__ . '/data/bug-14446-rule.php');

		$analyser = self::getContainer()->getByType(Analyser::class);
		$finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class);
		$errors = $finalizer->finalize(
			$analyser->analyse([$file], null, null, true),
			false,
			true,
		)->getErrors();

		$this->assertNoErrors($errors);
	}

}
