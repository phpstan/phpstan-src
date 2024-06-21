<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class ParamOutTypeTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/param-out-php7.php');
		}
		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/param-out-php8.php');
		}
		yield from $this->gatherAssertTypes(__DIR__ . '/data/param-out.php');
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
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/typeAliases.neon',
			__DIR__ . '/param-out.neon',
		];
	}

}
