<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ScopePhpVersionRangeReturnTypeExtensionsTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/scope-php-version-range-return-type-extensions.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/loose-equality-php-version-range.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/readonly-property-php-version-range.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/array-unpacking-php-version-range.php');
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
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/scopePhpVersionRange.neon',
		];
	}

}
