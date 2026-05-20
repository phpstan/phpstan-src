<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<IfConstantConditionRule>
 */
class Bug14655Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IfConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					true,
				),
				true,
			),
			new PossiblyImpureTipHelper(true),
			true,
			true,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14655.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/bug-14655.neon'],
		);
	}

}
