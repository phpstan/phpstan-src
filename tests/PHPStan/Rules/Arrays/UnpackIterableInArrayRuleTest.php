<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_merge;
use function usort;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnpackIterableInArrayRule>
 */
class UnpackIterableInArrayRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		return new UnpackIterableInArrayRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unpack-iterable.php'], [
			[
				'Only iterables can be unpacked, array<int>|null given.',
				21,
			],
			[
				'Only iterables can be unpacked, int given.',
				22,
			],
			[
				'Only iterables can be unpacked, string given.',
				23,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/unpack-iterable-nullsafe.php'], [
			[
				'Only iterables can be unpacked, array<int>|null given.',
				17,
			],
		]);
	}

	public function dataMixed(): array
	{
		$explicitOnlyErrors = [
			[
				'Only iterables can be unpacked, T of mixed given.',
				11,
			],
			[
				'Only iterables can be unpacked, mixed given.',
				12,
			],
		];
		$implicitOnlyErrors = [
			[
				'Only iterables can be unpacked, mixed given.',
				13,
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
		$this->analyse([__DIR__ . '/data/unpack-mixed.php'], $errors);
	}

}
