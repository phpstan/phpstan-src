<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const DIRECTORY_SEPARATOR;

class PathConstantsTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			yield from self::gatherAssertTypes(__DIR__ . '/data/pathConstants-win.php');
		} else {
			yield from self::gatherAssertTypes(__DIR__ . '/data/pathConstants.php');
		}
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataFileAsserts')]
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
