<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeSpecifyingExtensionTypeInferenceNullTest extends TypeInferenceTestCase
{

	public static function dataTypeSpecifyingExtensionsNull(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-1-null.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-2-null.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-3-null.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataTypeSpecifyingExtensionsNull')]
	public function testTypeSpecifyingExtensionsNull(
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
			__DIR__ . '/TypeSpecifyingExtension-null.neon',
		];
	}

}
