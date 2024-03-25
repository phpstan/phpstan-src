<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class ParamClosureThisStubsTest extends TypeInferenceTestCase
{

	public function dataAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/param-closure-this-stubs.php');
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
			__DIR__ . '/param-closure-this-stubs.neon',
		];
	}

}
