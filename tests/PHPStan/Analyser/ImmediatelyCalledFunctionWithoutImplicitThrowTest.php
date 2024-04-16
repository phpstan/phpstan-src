<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class ImmediatelyCalledFunctionWithoutImplicitThrowTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/immediately-called-function-without-implicit-throw.php');
	}

	/**
	 * @dataProvider dataFileAsserts
	 * @param mixed ...$args
	 */
	public function testFileAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/data/immediately-called-function-without-implicit-throw.neon']
		);
	}

}
