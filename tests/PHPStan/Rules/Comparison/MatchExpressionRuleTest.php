<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class MatchExpressionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new MatchExpressionRule(
				new ConstantConditionRuleHelper(
					new ImpossibleCheckTypeHelper(
						self::createReflectionProvider(),
						$this->getTypeSpecifier(),
						[],
						$this->treatPhpDocTypesAsCertain,
					),
					$this->treatPhpDocTypesAsCertain,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				$this->treatPhpDocTypesAsCertain,
			),
			new ConstantConditionInTraitRule(),
		]);
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
				'Match arm comparison between 3 and 3 is always true.',
				35,
				$tipText,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				40,
				$tipText,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				46,
				$tipText,
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

	#[RequiresPhp('>= 8.0.0')]
	public function testBug5454(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5454.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testEnums(): void
	{
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
				'Match arm comparison between MatchEnums\Foo and MatchEnums\Foo::ONE is always false.',
				85,
			],
			[
				'Match arm comparison between MatchEnums\Foo::THREE|MatchEnums\Foo::TWO and MatchEnums\DifferentEnum::ONE is always false.',
				95,
			],
			[
				'Match arm comparison between MatchEnums\Foo and MatchEnums\Foo::ONE is always false.',
				104,
			],
			[
				'Match arm comparison between MatchEnums\Foo::THREE|MatchEnums\Foo::TWO and MatchEnums\DifferentEnum::ONE is always false.',
				113,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug6394(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6394.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug6115(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6115.php'], [
			[
				'Match expression does not handle remaining value: 3',
				32,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7095(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7095.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug7176(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7176.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug6064(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6064.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug6647(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6647.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7622(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7622.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug7698(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7698.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug7746(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7746.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug8240(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8240.php'], [
			[
				'Match arm comparison between Bug8240\Foo::BAR and Bug8240\Foo::BAR is always true.',
				13,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Match arm comparison between Bug8240\Foo2::BAZ and Bug8240\Foo2::BAZ is always true.',
				28,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testLastArmAlwaysTrue(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Remove remaining cases below this one and this error will disappear too.';
		$this->analyse([__DIR__ . '/data/last-match-arm-always-true.php'], [
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Foo)&LastMatchArmAlwaysTrue\Foo::TWO and LastMatchArmAlwaysTrue\Foo::TWO is always true.',
				22,
				$tipText,
			],
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Foo)&LastMatchArmAlwaysTrue\Foo::TWO and LastMatchArmAlwaysTrue\Foo::TWO is always true.',
				31,
				$tipText,
			],
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Foo)&LastMatchArmAlwaysTrue\Foo::TWO and LastMatchArmAlwaysTrue\Foo::TWO is always true.',
				40,
				$tipText,
			],
			[
				'Match arm comparison between $this(LastMatchArmAlwaysTrue\Bar)&LastMatchArmAlwaysTrue\Bar::ONE and LastMatchArmAlwaysTrue\Bar::ONE is always true.',
				62,
				$tipText,
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

	#[RequiresPhp('>= 8.1.0')]
	public function testLastCondition(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/match-always-true-last-arm.php'], [
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
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug8932(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-8932.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug8937(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-8937.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug8900(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8900.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug4451(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4451.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9007(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9007.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9457(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9457.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug8614(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8614.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug8536(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8536.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9499(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9499.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug6407(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6407.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBugUnhandledTrueWithComplexCondition(): void
	{
		$this->analyse([__DIR__ . '/data/bug-unhandled-true-with-complex-condition.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug11246(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11246.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9879(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9879.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug11313(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11313.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug9436(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9436.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug11852(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11852.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/match-expr-property-hooks.php'], [
			[
				'Match expression does not handle remaining value: 3',
				13,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13048(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13048.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug13303(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13303.php'], [
			[
				'Match expression does not handle remaining value: true',
				34,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12998(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12998.php'], [
			[
				'Match expression does not handle remaining value: class-string<T of Bug12998\C|Bug12998\D>',
				37,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug9534(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9534.php'], [
			[
				'Match expression does not handle remaining value: class-string<Bug9534\Bike|Bug9534\Boat|Bug9534\Car>',
				21,
			],
			[
				'Match expression does not handle remaining value: class-string<Bug9534\FinalBike|Bug9534\FinalBoat>',
				47,
			],
			[
				'Match expression does not handle remaining value: class-string<Bug9534\Bike|Bug9534\Car>',
				58,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12241(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12241.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug14412(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14412.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug13029(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13029.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12790(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12790.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug11310(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11310.php'], [
			[
				'Match arm comparison between int<1, max> and 0 is always false.',
				24,
			],
			[
				'Match arm comparison between int<1, 6>|int<8, 14> and 0 is always false.',
				49,
			],
			[
				'Match arm comparison between 1|2|3|4|5|6 and 0 is always false.',
				74,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testInTrait(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/match-in-trait.php'], [
			[
				'Match arm comparison between true and false is always false.',
				21,
			],
		]);
	}

}
