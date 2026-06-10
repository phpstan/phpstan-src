<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Temporary test for the "new world" (single-pass ExpressionResult callbacks,
 * see NEW_WORLD.md): asserts type inference for the already-migrated
 * ExprHandlers and for the guarded legacy bridges.
 *
 * Two ways to run it, toggled by the NewWorld::disableOldWorld() literal:
 * - false (the committed state — mixed mode, PHP 8.1+, PHPSTAN_FNSR unset or
 *   != 0): it must be fully green — migrated constructs take the new-world
 *   callbacks, unmigrated ones exercise the guarded legacy bridges on purpose.
 * - true (flipped when starting to migrate a handler): the guard exceptions
 *   are active and this test is the migration meter — the first failure names
 *   the handler that still needs to implement the new callbacks (fix, rerun,
 *   next).
 *
 * The goal of the whole refactoring is the entire test suite green in mixed
 * mode — every migrated handler improves analysis precision across the whole
 * suite before the rewrite is finished. Delete this test once that is reached —
 * everything here is covered by pre-existing tests.
 */
class NewWorldTypeInferenceTest extends TypeInferenceTestCase
{

	public static function dataAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/data/new-world.php');
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
		return [];
	}

}
