<?php

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class WeirdBugTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/weird-bug.php');
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
		return [
			__DIR__ . '/weird-bug-config.neon',
		];
	}

}
