<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PipeOperatorRule>
 */
class PipeOperatorRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new PipeOperatorRule(
			new RuleLevelHelper(
				self::createReflectionProvider(),
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: true,
				checkImplicitMixed: true,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
		);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pipe-operator.php'], [
			[
				'Parameter #1 of callable on the right side of pipe operator is passed by reference.',
				14,
			],
			[
				'Parameter #1 $s of callable on the right side of pipe operator is passed by reference.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testBug14150(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14150-pipe.php'], [
			[
				'Parameter #1 $s of callable on the right side of pipe operator is passed by reference.',
				17,
			],
		]);
	}

}
