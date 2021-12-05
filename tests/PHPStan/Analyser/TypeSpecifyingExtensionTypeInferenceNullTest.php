<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class TypeSpecifyingExtensionTypeInferenceNullTest extends TypeInferenceTestCase
{

	public function dataTypeSpecifyingExtensionsNull(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-1-null.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-2-null.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-3-null.php');
	}

	/**
	 * @dataProvider dataTypeSpecifyingExtensionsNull
	 * @param mixed ...$args
	 */
	public function testTypeSpecifyingExtensionsNull(
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
			__DIR__ . '/TypeSpecifyingExtension-null.neon',
		];
	}

}
