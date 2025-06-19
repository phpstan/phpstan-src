<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeSpecifyingExtensionTypeInferenceTrueTest extends TypeInferenceTestCase
{

	public static function dataTypeSpecifyingExtensionsTrue(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-1-true.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-2-true.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-3-true.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataTypeSpecifyingExtensionsTrue')]
	public function testTypeSpecifyingExtensionsTrue(
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
			__DIR__ . '/TypeSpecifyingExtension-true.neon',
		];
	}

}
