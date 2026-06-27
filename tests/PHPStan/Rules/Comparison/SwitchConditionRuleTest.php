<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function define;
use function defined;
use const PHP_VERSION_ID;

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
				self::getContainer()->getByType(ExprPrinter::class),
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
				'Case \'lb\' in switch is a duplicate of case \'lb\' on line 24.',
				30,
			],
			[
				'Case \'oz\' in switch is a duplicate of case \'oz\' on line 27.',
				33,
			],
			[
				'Case 1 in switch is a duplicate of case 1 on line 42.',
				46,
			],
			[
				'Case \'x\' in switch is a duplicate of case \'x\' on line 54.',
				58,
			],
			[
				'Case \'x\' in switch is a duplicate of case \'x\' on line 54.',
				60,
			],
			[
				'Case self::EQ in switch is a duplicate of case \'=\' on line 68.',
				72,
			],
			[
				'Case DUPLICATE_SWITCH_CASE_CONST in switch is a duplicate of case \'unknown\' on line 80.',
				82,
			],
			[
				'Case \'a\' in switch is a duplicate of case \'a\' on line 90.',
				94,
			],
			[
				'Case true in switch is a duplicate of case true on line 106.',
				110,
			],
			[
				'Case null in switch is a duplicate of case null on line 108.',
				112,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRuleEnum(): void
	{
		$this->analyse([__DIR__ . '/data/switch-condition-always-false-enum.php'], [
			[
				'Case \SwitchConditionAlwaysFalseEnum\Status::Active in switch is a duplicate of case \SwitchConditionAlwaysFalseEnum\Status::Active on line 20.',
				24,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
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
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
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
		]);
	}

	public function testDoNotTreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = false;

		if (PHP_VERSION_ID < 80000) {
			// Before PHP 8.0 a non-numeric string loosely equals 0, so int == 'foo' is not always false.
			$this->analyse([__DIR__ . '/data/switch-condition-always-false-native.php'], []);
			return;
		}

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
