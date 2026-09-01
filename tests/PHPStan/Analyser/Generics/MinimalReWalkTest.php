<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Testing\TypeInferenceTestCase;
use function array_merge;

/**
 * The second pass of a body re-walks only the statements the resolved
 * template arguments can influence; the rest replay their recording.
 */
class MinimalReWalkTest extends TypeInferenceTestCase
{

	public function testStatementsNotMentioningTheSiteVariableAreReplayed(): void
	{
		TemplateArgumentStats::reset();
		TemplateArgumentStats::$enabled = true;
		try {
			foreach (self::gatherAssertTypes(__DIR__ . '/data/minimal-rewalk.php') as $args) {
				$this->assertFileAsserts(...$args);
			}
			$counters = TemplateArgumentStats::getCounters();
		} finally {
			TemplateArgumentStats::$enabled = false;
		}

		$this->assertSame(1, $counters['bodiesWithSites']);
		// the `new`, the property send, and the assertType() reading the variable
		$this->assertSame(3, $counters['statementsReWalked']);
		// the ten statements never mentioning $c
		$this->assertSame(10, $counters['statementsReplayed']);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/../../../../conf/bleedingEdge.neon'],
		);
	}

}
