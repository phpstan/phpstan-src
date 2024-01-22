<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_merge;
use function usort;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<IterableInForeachRule>
 */
class IterableInForeachRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		return new IterableInForeachRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, true, false));
	}

	public function testCheckWithMaybes(): void
	{
		$this->analyse([__DIR__ . '/data/foreach-iterable.php'], [
			[
				'Argument of an invalid type string supplied for foreach, only iterables are supported.',
				10,
			],
			[
				'Argument of an invalid type array<int, int>|false supplied for foreach, only iterables are supported.',
				19,
			],
			[
				'Iterating over an object of an unknown class IterablesInForeach\Bar.',
				47,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug5744(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5744.php'], [
			/*[
				'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
				15,
			],*/
			[
				'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
				28,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/foreach-iterable-nullsafe.php'], [
			[
				'Argument of an invalid type array<int>|null supplied for foreach, only iterables are supported.',
				14,
			],
		]);
	}

	public function testBug6564(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6564.php'], []);
	}

	public function testBug4335(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4335.php'], []);
	}

	public function dataMixed(): array
	{
		$explicitOnlyErrors = [
			[
				'Argument of an invalid type T of mixed supplied for foreach, only iterables are supported.',
				11,
			],
			[
				'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
				14,
			],
		];
		$implicitOnlyErrors = [
			[
				'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
				17,
			],
		];
		$combinedErrors = array_merge($explicitOnlyErrors, $implicitOnlyErrors);
		usort($combinedErrors, static fn (array $a, array $b): int => $a[1] <=> $b[1]);

		return [
			[
				true,
				false,
				$explicitOnlyErrors,
			],
			[
				false,
				true,
				$implicitOnlyErrors,
			],
			[
				true,
				true,
				$combinedErrors,
			],
			[
				false,
				false,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataMixed
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testMixed(bool $checkExplicitMixed, bool $checkImplicitMixed, array $errors): void
	{
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->checkImplicitMixed = $checkImplicitMixed;
		$this->analyse([__DIR__ . '/data/foreach-mixed.php'], $errors);
	}

}
