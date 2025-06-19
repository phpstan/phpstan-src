<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DoNotRememberPossiblyImpureFunctionValuesTest extends TypeInferenceTestCase
{

	public static function dataAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/do-not-remember-possibly-impure-function-values.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataAsserts')]
	public function testAsserts(
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
			__DIR__ . '/do-not-remember-possibly-impure-function-values.neon',
		];
	}

}
