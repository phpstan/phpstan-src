<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class ParameterOutTypeExtensionTest extends TypeInferenceTestCase
{

	public function dataAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/param-out/parameter-out-types.php');
	}

	/**
	 * @dataProvider dataAsserts
	 * @param mixed ...$args
	 */
	public function testAsserts(
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
			__DIR__ . '/parameter-out.neon',
		];
	}

}
