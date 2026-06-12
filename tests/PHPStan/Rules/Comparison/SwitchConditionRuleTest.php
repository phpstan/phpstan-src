<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use function define;
use function defined;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class SwitchConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new SwitchConditionRule(
				new ConstantConditionRuleHelper(
					new ImpossibleCheckTypeHelper(
						self::createReflectionProvider(),
						$this->getTypeSpecifier(),
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
		if (!defined('DUPLICATE_SWITCH_CASE_CONST')) {
			define('DUPLICATE_SWITCH_CASE_CONST', 'unknown');
		}

		$this->analyse([__DIR__ . '/data/switch-condition-always-false.php'], [
			[
				'Switch condition comparison between int<min, 0>|int<3, max> and 1 is always false.',
				46,
			],
			[
				'Switch condition comparison between \'0\' and true is always false.',
				107,
			],
			[
				'Switch condition comparison between \'0\' and null is always false.',
				109,
			],
		]);
	}

	public function testRuleEnum(): void
	{
		$this->analyse([__DIR__ . '/data/switch-condition-always-false-enum.php'], [
			[
				'Switch condition comparison between SwitchConditionAlwaysFalseEnum\Status::Pending and SwitchConditionAlwaysFalseEnum\Status::Active is always false.',
				24,
			],
		]);
	}

	public function testAlwaysTrue(): void
	{
		$tipText = 'Remove remaining cases below this one and this error will disappear too.';
		$this->analyse([__DIR__ . '/data/switch-condition-always-true.php'], [
			[
				'Switch condition comparison between SwitchConditionAlwaysTrue\Suit::Diamonds and SwitchConditionAlwaysTrue\Suit::Diamonds is always true.',
				21,
				$tipText,
			],
			[
				'Switch condition comparison between 2 and 2 is always true.',
				46,
				$tipText,
			],
			[
				'Switch condition comparison between SwitchConditionAlwaysTrue\Suit::Diamonds and SwitchConditionAlwaysTrue\Suit::Diamonds is always true.',
				58,
				$tipText,
			],
		]);
	}

	public function testImpossibleCases(): void
	{
		$tipText = 'Remove remaining cases below this one and this error will disappear too.';
		$this->analyse([__DIR__ . '/data/switch-condition-always-false-impossible.php'], [
			[
				'Switch condition comparison between int and \'foo\' is always false.',
				11,
			],
			[
				'Switch condition comparison between 1|2|3 and 4 is always false.',
				22,
			],
			[
				'Switch condition comparison between \'b\' and \'b\' is always true.',
				37,
				$tipText,
			],
			[
				'Switch condition comparison between int<5, max> and 1 is always false.',
				50,
			],
			[
				'Switch condition comparison between *NEVER* and string is always false.',
				66,
			],
		]);
	}

	public function testDoNotTreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/switch-condition-always-false-native.php'], [
			[
				'Switch condition comparison between int and \'foo\' is always false.',
				11,
			],
		]);
	}

	public function testInTrait(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/switch-condition-in-trait.php'], [
			[
				'Switch condition comparison between true and false is always false.',
				21,
			],
			[
				'Switch condition comparison between true and true is always true.',
				30,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

}
