<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class ForeachLoopNoScopePollutionTest extends TypeInferenceTestCase
{

	/** @return iterable<array<string, mixed[]>> */
	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/foreach-loop-no-scope-pollution.php');
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
			__DIR__ . '/foreachLoopNoScopePollution.neon',
		];
	}

}
