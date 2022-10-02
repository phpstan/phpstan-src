<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class DynamicMethodThrowTypeExtensionTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			return [];
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-throw-type-extension.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-throw-type-extension-named-args-fixture.php');
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
			__DIR__ . '/dynamic-throw-type-extension.neon',
		];
	}

}
