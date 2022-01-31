<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class TypeSpecifyingExtensionTypeInferenceTrueTest extends TypeInferenceTestCase
{

	public function dataTypeSpecifyingExtensionsTrue(): iterable
	{
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-1-true.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-2-true.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/type-specifying-extensions-3-true.php');
	}

	/**
	 * @dataProvider dataTypeSpecifyingExtensionsTrue
	 */
	public function testTypeSpecifyingExtensionsTrue(string $file): void
	{
		$this->assertFileAssertsLazy($file);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/TypeSpecifyingExtension-true.neon',
		];
	}

}
