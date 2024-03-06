<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TooWideParameterOutTypeRule>
 */
class TooWideParameterOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new TooWideParameterOutTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-parameter-out.php'], [
			[
				'Method TooWideParameterOut\Foo::doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				15,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideParameterOut\Foo::doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				23,
			],
			[
				'Function TooWideParameterOut\doFoo() never assigns null to &$p so it can be removed from the by-ref type.',
				30,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

}
