<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Testing\TypeInferenceTestCase;

class AssertIfTrueOnThisTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-13358.php');
	}

	/**
	 * @dataProvider dataFileAsserts
	 * @param mixed ...$args
	 */
	public function testFileAsserts(string $assertType, string $file, ...$args): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

}
