<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class DynamicParameterTypeExtensionTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		if (PHP_VERSION_ID < 70400) {
			return [];
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-parameter-type-extension-arrow-function.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-parameter-type-extension-closure.php');
		// yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-parameter-type-extension-non-closure.php');
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
			__DIR__ . '/dynamic-parameter-type-extension.neon',
		];
	}

}
