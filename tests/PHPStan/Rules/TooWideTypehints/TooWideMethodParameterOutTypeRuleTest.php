<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TooWideMethodParameterOutTypeRule>
 */
class TooWideMethodParameterOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new TooWideMethodParameterOutTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-method-parameter-out.php'], [
			[
				'Method TooWideMethodParameterOut\Foo::doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				13,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\Foo::doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				21,
			],
			[
				'Method TooWideMethodParameterOut\Foo::doLorem() never assigns null to &$p so it can be removed from the by-ref type.',
				26,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

	public function testBug10684(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10684.php'], []);
	}

	public function testBug10687(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10687.php'], []);
	}

}
