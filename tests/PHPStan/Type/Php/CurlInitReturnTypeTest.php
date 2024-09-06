<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Testing\TypeInferenceTestCase;
use const PHP_VERSION_ID;

class CurlInitReturnTypeTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			yield from self::gatherAssertTypes(__DIR__ . '/data/curl-init-php-7.php');
		} else {
			yield from self::gatherAssertTypes(__DIR__ . '/data/curl-init-php-8.php');
		}
	}

	/**
	 * @dataProvider dataFileAsserts
	 */
	public function testFileAsserts(
		string $assertType,
		string $file,
		mixed ...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

}
