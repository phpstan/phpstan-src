<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>= 8.0.0')]
class DynamicParameterTypeExtensionArraysTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/dynamic-parameter-type-extension-arrays.php');
	}

	/** @param mixed ...$args */
	#[DataProvider('dataFileAsserts')]
	public function testFileAsserts(string $assertType, string $file, ...$args): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/dynamic-parameter-type-extension-arrays.neon'];
	}

}
