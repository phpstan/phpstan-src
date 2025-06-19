<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;

class ImmediatelyCalledFunctionWithoutImplicitThrowTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/immediately-called-function-without-implicit-throw.php');
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
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/data/immediately-called-function-without-implicit-throw.neon'],
		);
	}

}
