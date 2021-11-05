<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class DynamicMethodThrowTypeExtensionTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-throw-type-extension.php');

		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-throw-type-extension-named-args.php');
		}
	}

	/**
	 * @dataProvider dataFileAsserts
	 * @param string $assertType
	 * @param string $file
	 * @param mixed ...$args
	 */
	public function testFileAsserts(
		string $assertType,
		string $file,
		...$args
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/dynamic-throw-type-extension.neon',
		];
	}

}
