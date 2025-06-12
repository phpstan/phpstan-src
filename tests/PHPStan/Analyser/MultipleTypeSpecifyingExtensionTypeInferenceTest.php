<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class MultipleTypeSpecifyingExtensionTypeInferenceTest extends TypeInferenceTestCase
{

	public function dataTypeSpecifyingExtensionsTrue(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/multiple-type-specifying-extensions-1.php');
	}

	/**
	 * @dataProvider dataTypeSpecifyingExtensionsTrue
	 * @param mixed ...$args
	 */
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
			__DIR__ . '/MultipleTypeSpecifyingExtension.neon',
		];
	}

}
