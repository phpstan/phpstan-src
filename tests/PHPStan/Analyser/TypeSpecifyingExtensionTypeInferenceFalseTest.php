<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class TypeSpecifyingExtensionTypeInferenceFalseTest extends TypeInferenceTestCase
{

	public function dataTypeSpecifyingExtensionsFalse(): iterable
	{
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-1-false.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-2-false.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-3-false.php');
	}

	/**
	 * @dataProvider dataTypeSpecifyingExtensionsFalse
	 */
	public function testTypeSpecifyingExtensionsFalse(string $file): void
	{
		$this->assertFileAssertsLazy($file);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/TypeSpecifyingExtension-false.neon',
		];
	}

}
