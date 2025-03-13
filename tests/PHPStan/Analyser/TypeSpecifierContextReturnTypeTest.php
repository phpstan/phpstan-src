<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Testing\TypeInferenceTestCase;

class TypeSpecifierContextReturnTypeTest extends TypeInferenceTestCase
{
	public function dataContextReturnType(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/type-specifier-context-return-type.php');
	}

	/**
	 * @dataProvider dataContextReturnType
	 * @param mixed ...$args
	 */
	public function testContextReturnType(
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
			__DIR__.'/TypeSpecifierContextReturnTypeExtension.neon'
		];
	}

}
