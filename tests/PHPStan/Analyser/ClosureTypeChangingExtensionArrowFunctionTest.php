<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class ClosureTypeChangingExtensionArrowFunctionTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		if (PHP_VERSION_ID < 70400) {
			return [];
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/data/closure-type-changing-extension-arrow-function.php');
	}

	/**
	 * @dataProvider dataFileAsserts
	 * @param mixed ...$args
	 */
	public function testFileAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/closure-type-changing-extension-arrow-function.neon',
		];
	}

}
