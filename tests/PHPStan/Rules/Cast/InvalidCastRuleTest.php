<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_merge;
use function usort;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidCastRule>
 */
class InvalidCastRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new InvalidCastRule($broker, new RuleLevelHelper($broker, true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-cast.php'], [
			[
				'Cannot cast stdClass to string.',
				7,
			],
			[
				'Cannot cast stdClass to int.',
				23,
			],
			[
				'Cannot cast stdClass to float.',
				24,
			],
			[
				'Cannot cast object to string.',
				35,
			],
			[
				'Cannot cast Test\\Foo to string.',
				41,
			],
			[
				'Cannot cast array|float|int to string.',
				48,
			],
		]);
	}

	public function testBug5162(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5162.php'], []);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/invalid-cast-nullsafe.php'], [
			[
				'Cannot cast stdClass|null to string.',
				13,
			],
		]);
	}

	public function testCastObjectToString(): void
	{
		$this->analyse([__DIR__ . '/data/cast-object-to-string.php'], [
			[
				'Cannot cast object to string.',
				12,
			],
			[
				'Cannot cast object|string to string.',
				13,
			],
		]);
	}

	public function dataMixed(): array
	{
		$explicitOnlyErrors = [
			[
				'Cannot cast T to int.',
				11,
			],
			[
				'Cannot cast T to float.',
				13,
			],
			[
				'Cannot cast T to string.',
				14,
			],
			[
				'Cannot cast mixed to int.',
				18,
			],
			[
				'Cannot cast mixed to float.',
				20,
			],
			[
				'Cannot cast mixed to string.',
				21,
			],
		];
		$implicitOnlyErrors = [
			[
				'Cannot cast mixed to int.',
				25,
			],
			[
				'Cannot cast mixed to float.',
				27,
			],
			[
				'Cannot cast mixed to string.',
				28,
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
		$this->checkImplicitMixed = $checkImplicitMixed;
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->analyse([__DIR__ . '/data/mixed-cast.php'], $errors);
	}

}
