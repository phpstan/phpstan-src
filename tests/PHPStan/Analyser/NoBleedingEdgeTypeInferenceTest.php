<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Runs without bleeding edge.
 */
class NoBleedingEdgeTypeInferenceTest extends TypeInferenceTestCase
{

	public static function dataAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-15534.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-15736.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-15749.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-15760.php');
		yield from self::gatherAssertTypes(__DIR__ . '/nsrt/bug-15775.php');
		yield from self::gatherAssertTypes(__DIR__ . '/nsrt/bug-15780.php');
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

}
