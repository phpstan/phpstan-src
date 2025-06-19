<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

class DynamicMethodThrowTypeExtensionTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			return [];
		}

		yield from self::gatherAssertTypes(__DIR__ . '/data/dynamic-method-throw-type-extension.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/dynamic-method-throw-type-extension-named-args-fixture.php');
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
			__DIR__ . '/dynamic-throw-type-extension.neon',
		];
	}

}
