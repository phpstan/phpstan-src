<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;

/**
 * With backedEnumFromParameterType off, from() and tryFrom() of a backed
 * enum keep the int|string parameter reflection declares.
 */
#[RequiresPhp('>= 8.1.0')]
class BackedEnumFromParameterTypeToggleOffTest extends TypeInferenceTestCase
{

	/** @return iterable<mixed> */
	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/backed-enum-from-parameter-type-off.php');
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
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
			__DIR__ . '/backed-enum-from-parameter-type-off.neon',
		]);
	}

}
