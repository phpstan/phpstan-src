<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UnaryOperatorTypeSpecifyingExtensionTypeInferenceTest extends TypeInferenceTestCase
{

	public static function dataAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/unary-operator-type-specifying-extension.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataAsserts')]
	public function testAsserts(
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
			__DIR__ . '/unary-operator-type-specifying-extension.neon',
		];
	}

}
