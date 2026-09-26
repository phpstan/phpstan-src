<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;

/**
 * With unresolved template arguments on but closure signature inference off,
 * a closure written where nothing types it keeps its declared signature.
 */
class ClosureSignatureFromUsagesToggleOffTest extends TypeInferenceTestCase
{

	/** @return iterable<mixed> */
	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/closure-signature-from-usages-off.php');
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
			__DIR__ . '/closure-signatures-off.neon',
		]);
	}

}
