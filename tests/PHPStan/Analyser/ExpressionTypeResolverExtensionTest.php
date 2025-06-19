<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ExpressionTypeResolverExtensionTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/expression-type-resolver-extension.php');
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
			__DIR__ . '/expression-type-resolver-extension.neon',
		];
	}

}
