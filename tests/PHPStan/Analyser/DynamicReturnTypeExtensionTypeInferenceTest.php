<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class DynamicReturnTypeExtensionTypeInferenceTest extends TypeInferenceTestCase
{

	public function dataAsserts(): iterable
	{
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/dynamic-method-return-types.php');
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/dynamic-method-return-compound-types.php');
	}

	/**
	 * @dataProvider dataAsserts
	 */
	public function testAsserts(string $file): void
	{
		$this->assertFileAssertsLazy($file);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/dynamic-return-type.neon',
		];
	}

}
