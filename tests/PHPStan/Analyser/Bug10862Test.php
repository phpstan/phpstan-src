<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class Bug10862Test extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		$path = 'bug-10862-php8.3';
		if (PHP_VERSION_ID < 80300) {
			$path = 'bug-10862-php8.0';
		}
		if (PHP_VERSION_ID < 80000) {
			$path = 'bug-10862';
		}
		yield from self::gatherAssertTypes(__DIR__ . '/data/' . $path . '.php');
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

}
