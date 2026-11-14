<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

class TypeSpecifyingExtensionIdenticalFunctionCallTest extends TypeInferenceTestCase
{

	public static function dataAsserts(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			return [];
		}

		yield from self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-identical-function-call.php');
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
			__DIR__ . '/TypeSpecifyingExtensionIdenticalFunctionCall.neon',
		];
	}

}
