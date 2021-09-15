<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class DynamicReturnTypeExtensionTypeInferenceTest extends TypeInferenceTestCase
{

	public function dataAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-return-types.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-return-compound-types.php');
	}

	/**
	 * @dataProvider dataAsserts
	 * @param string $assertType
	 * @param string $file
	 * @param mixed ...$args
	 */
	public function testAsserts(
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
			__DIR__ . '/dynamic-return-type.neon',
		];
	}

}
