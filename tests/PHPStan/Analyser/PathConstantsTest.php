<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const DIRECTORY_SEPARATOR;

class PathConstantsTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/pathConstants-win.php');
		} else {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/pathConstants.php');
		}
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
			__DIR__ . '/usePathConstantsAsConstantString.neon',
		];
	}

}
