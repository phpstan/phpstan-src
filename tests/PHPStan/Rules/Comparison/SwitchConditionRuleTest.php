<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function define;
use function defined;

/**
 * @extends RuleTestCase<SwitchConditionRule>
 */
class SwitchConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new SwitchConditionRule(
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
		);
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
				'Switch condition comparison between int and 1 is always false.',
				46,
			],
			[
				'Switch condition comparison between mixed and true is always false.',
				107,
			],
			[
				'Switch condition comparison between mixed and null is always false.',
				109,
			],
		]);
	}

	public function testRuleEnum(): void
	{
		$this->analyse([__DIR__ . '/data/switch-condition-always-false-enum.php'], [
			[
				'Switch condition comparison between SwitchConditionAlwaysFalseEnum\Status and SwitchConditionAlwaysFalseEnum\Status::Active is always false.',
				24,
			],
		]);
	}

	public function testImpossibleCases(): void
	{
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
				'Switch condition comparison between \'a\'|\'b\' and \'c\' is always false.',
				39,
			],
			[
				'Switch condition comparison between int<5, max> and 1 is always false.',
				50,
			],
			[
				'Switch condition comparison between \'a\'|\'b\' and string is always false.',
				67,
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

}
