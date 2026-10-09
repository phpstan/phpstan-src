<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

class ArrayArgumentSkeletonTest extends TypeInferenceTestCase
{

	public static function dataAsserts(): iterable
	{
		if (PHP_VERSION_ID < 80100) {
			return;
		}
		yield from self::gatherAssertTypes(__DIR__ . '/nsrt/bug-15432-callable.php');
		yield from self::gatherAssertTypes(__DIR__ . '/nsrt/bug-15432-array-effects.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataAsserts')]
	#[RequiresPhp('>= 8.1.0')]
	public function testAsserts(string $assertType, string $file, ...$args): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

}
