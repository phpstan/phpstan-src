<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class LooseConstComparisonPhp8Test extends TypeInferenceTestCase
{

	/**
	 * @return iterable<array<string, mixed[]>>
	 */
	public function dataFileAsserts(): iterable
	{
		// compares constants according to the php-version phpstan configuration,
		// _NOT_ the current php runtime version
		yield from $this->gatherAssertTypes(__DIR__ . '/data/loose-const-comparison-php8.php');
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
			__DIR__ . '/looseConstComparisonPhp8.neon',
		];
	}

}
