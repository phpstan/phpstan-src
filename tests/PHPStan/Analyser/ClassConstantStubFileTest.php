<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class ClassConstantStubFileTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypesLazy(__DIR__ . '/data/class-constant-stub-files.php');
	}

	/**
	 * @dataProvider dataFileAsserts
	 */
	public function testFileAsserts(string $file): void
	{
		$this->assertFileAssertsLazy($file);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/classConstantStubFiles.neon',
		];
	}

}
