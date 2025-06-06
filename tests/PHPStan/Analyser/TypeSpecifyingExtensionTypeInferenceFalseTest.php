<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class TypeSpecifyingExtensionTypeInferenceFalseTest extends TypeInferenceTestCase
{

	public static function dataTypeSpecifyingExtensionsFalse(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-1-false.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-2-false.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-3-false.php');
	}

	/**
	 * @dataProvider dataTypeSpecifyingExtensionsFalse
	 * @param mixed ...$args
	 */
	public function testTypeSpecifyingExtensionsFalse(
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
			__DIR__ . '/TypeSpecifyingExtension-false.neon',
		];
	}

}
