<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MatchExpressionRule>
 */
class MatchExpressionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	private bool $reportAlwaysTrueInLastCondition = false;

	private bool $disableUnreachable = false;

	protected function getRule(): Rule
	{
		return new MatchExpressionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain,
					true,
				),
				$this->treatPhpDocTypesAsCertain,
				true,
			),
			true,
			$this->disableUnreachable,
			$this->reportAlwaysTrueInLastCondition,
			$this->treatPhpDocTypesAsCertain,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$tipText = 'Remove remaining cases below this one and this error will disappear too.';
		$this->analyse([__DIR__ . '/data/match-expr.php'], [
			[
				'Match arm comparison between 1|2|3 and \'foo\' is always false.',
				14,
			],
			[
				'Match arm comparison between 1|2|3 and 0 is always false.',
				19,
			],
			[
				'Match arm comparison between 3 and 3 is always true.',
				28,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				29,
			],
			[
				'Match arm comparison between 3 and 3 is always true.',
				35,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				36,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				40,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				41,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				42,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				46,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				47,
			],
			[
				'Match expression does not handle remaining value: 3',
				50,
			],
			[
				'Match arm comparison between 1|2 and 3 is always false.',
				61,
			],
			[
				'Match expression does not handle remaining values: 1|2|3',
				78,
			],
			[
				'Match expression does not handle remaining value: true',
				90,
			],
			[
				'Match expression does not handle remaining values: int<min, 0>|int<2, max>',
				168,
			],
		]);
	}

	public function testBug5161(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5161.php'], []);
	}

	public function testBug4857(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4857.php'], [
			[
				'Match expression does not handle remaining value: true',
				13,
			],
			[
				'Match expression does not handle remaining value: true',
				23,
			],
		]);
	}

	public function testBug5454(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-5454.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/match-enums.php'], [
			[
				'Match expression does not handle remaining values: MatchEnums\Foo::THREE|MatchEnums\Foo::TWO',
				19,
			],
			[
				'Match expression does not handle remaining values: MatchEnums\Foo::THREE|MatchEnums\Foo::TWO',
				35,
			],
			[
				'Match expression does not handle remaining value: MatchEnums\Foo::THREE',
				56,
			],
			[
				'Match arm comparison between MatchEnums\Foo::THREE and MatchEnums\Foo::THREE is always true.',
				76,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				77,
			],
		]);
	}

	public function testBug6394(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-6394.php'], []);
	}

	public function testBug6115(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-6115.php'], [
			[
				'Match expression does not handle remaining value: 3',
				32,
			],
		]);
	}

	public function testBug7095(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-7095.php'], []);
	}

	public function testBug7176(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-7176.php'], []);
	}

	public function testBug6064(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-6064.php'], []);
	}

	public function testBug6647(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-6647.php'], []);
	}

	public function testBug7622(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7622.php'], []);
	}

	public function testBug7698(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7698.php'], []);
	}

	public function testBug7746(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7746.php'], []);
	}

	public function testBug8240(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8240.php'], [
			[
				'Match arm comparison between Bug8240\Foo and Bug8240\Foo::BAR is always true.',
				13,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				14,
			],
			[
				'Match arm comparison between Bug8240\Foo2::BAZ and Bug8240\Foo2::BAZ is always true.',
				28,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				29,
			],
		]);
	}

	public function testLastArmAlwaysTrue(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Remove remaining cases below this one and this error will disappear too.';
		$this->analyse([__DIR__ . '/data/last-match-arm-always-true.php'], [
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Foo)&LastMatchArmAlwaysTrue\Foo::TWO and LastMatchArmAlwaysTrue\Foo::TWO is always true.',
				22,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				23,
			],
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Foo)&LastMatchArmAlwaysTrue\Foo::TWO and LastMatchArmAlwaysTrue\Foo::TWO is always true.',
				31,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				32,
			],
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Foo)&LastMatchArmAlwaysTrue\Foo::TWO and LastMatchArmAlwaysTrue\Foo::TWO is always true.',
				40,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				41,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				42,
			],
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Bar) and LastMatchArmAlwaysTrue\Bar::ONE is always true.',
				62,
				$tipText,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				63,
			],
			[
				'Match arm comparison between 1 and 0 is always false.',
				70,
			],
			[
				'Match expression does not handle remaining value: 1',
				69,
			],
		]);
	}

	public function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, false, [
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				23,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				24,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				49,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				50,
			],
		]];
		yield [true, false, [
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				15,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				23,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				24,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				45,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				49,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				50,
			],
		]];
		yield [false, true, [
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				23,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				49,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, true, [
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				15,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				23,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				45,
			],
			[
				'Match arm comparison between $this(MatchAlwaysTrueLastArm\Foo)&MatchAlwaysTrueLastArm\Foo::BAR and MatchAlwaysTrueLastArm\Foo::BAR is always true.',
				49,
			],
		]];
	}

	/**
	 * @dataProvider dataReportAlwaysTrueInLastCondition
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReportAlwaysTrueInLastCondition(bool $reportAlwaysTrueInLastCondition, bool $disableUnreachable, array $expectedErrors): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->disableUnreachable = $disableUnreachable;
		$this->analyse([__DIR__ . '/data/match-always-true-last-arm.php'], $expectedErrors);
	}

	public function testBug8932(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-8932.php'], []);
	}

	public function testBug8937(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-8937.php'], []);
	}

	public function testBug8900(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-8900.php'], []);
	}

	public function testBug4451(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-4451.php'], []);
	}

	public function testBug9007(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-9007.php'], []);
	}

	public function testBug9457(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-9457.php'], []);
	}

	public function testBug8614(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-8614.php'], []);
	}

	public function testBug8536(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-8536.php'], []);
	}

	public function testBug9499(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-9499.php'], []);
	}

	public function testBug6407(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-6407.php'], []);
	}

}
