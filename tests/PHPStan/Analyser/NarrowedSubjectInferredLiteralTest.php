<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * Without bleedingEdge: the inferred template arguments are generalized right away
 * (featureToggles.unresolvedTemplateArguments is off), which is where a conditional
 * branch's subject has to keep what the branch knows about it.
 */
#[RequiresPhp('>= 8.1.0')]
class NarrowedSubjectInferredLiteralTest extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/narrowed-subject-inferred-literal.php');
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

}
