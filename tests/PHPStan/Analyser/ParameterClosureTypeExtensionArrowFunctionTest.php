<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ParameterClosureTypeExtensionArrowFunctionTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/parameter-closure-type-extension-arrow-function.php');
		if (PHP_VERSION_ID >= 80500) {
			yield from self::gatherAssertTypes(__DIR__ . '/data/closure-type-in-constant-expression-php85.php');
		}
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
			__DIR__ . '/parameter-closure-type-extension-arrow-function.neon',
		];
	}

}
