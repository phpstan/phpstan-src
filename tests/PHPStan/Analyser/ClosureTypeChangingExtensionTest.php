<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class ClosureTypeChangingExtensionTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/closure-type-changing-extension.php');

		if (PHP_VERSION_ID < 80000) {
			return;
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
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/closure-type-changing-extension.neon',
		];
	}

}
