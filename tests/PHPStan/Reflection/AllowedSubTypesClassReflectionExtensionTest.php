<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Testing\TypeInferenceTestCase;

class AllowedSubTypesClassReflectionExtensionTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		require_once __DIR__ . '/data/allowed-sub-types.php';
		yield from $this->gatherAssertTypes(__DIR__ . '/data/allowed-sub-types.php');
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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/data/allowed-sub-types.neon',
		];
	}

}
