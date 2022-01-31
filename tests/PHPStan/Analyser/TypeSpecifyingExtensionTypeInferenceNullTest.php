<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class TypeSpecifyingExtensionTypeInferenceNullTest extends TypeInferenceTestCase
{

	public function dataTypeSpecifyingExtensionsNull(): iterable
	{
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-1-null.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-2-null.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-3-null.php');
	}

	/**
	 * @dataProvider dataTypeSpecifyingExtensionsNull
	 */
	public function testTypeSpecifyingExtensionsNull(string $file): void
	{
		$this->assertFileAssertsLazy($file);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/TypeSpecifyingExtension-null.neon',
		];
	}

}
