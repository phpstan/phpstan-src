<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TooWideFunctionParameterOutTypeRule>
 */
class TooWideFunctionParameterOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new TooWideFunctionParameterOutTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-function-parameter-out.php'], [
			[
				'Function TooWideFunctionParameterOut\doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				10,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Function TooWideFunctionParameterOut\doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				18,
			],
			[
				'Function TooWideFunctionParameterOut\doLorem() never assigns null to &$p so it can be removed from the by-ref type.',
				23,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Function TooWideFunctionParameterOut\bug10699() never assigns 20 to &$out so it can be removed from the @param-out type.',
				48,
			],
		]);
	}

}
