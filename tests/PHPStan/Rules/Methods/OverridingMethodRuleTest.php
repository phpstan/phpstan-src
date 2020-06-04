<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OverridingMethodRule>
 */
class OverridingMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OverridingMethodRule();
	}

	public function testOverridingFinalMethod(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->analyse([__DIR__ . '/data/overriding-final-method.php'], [
			[
				'Method OverridingFinalMethod\Bar::doFoo() overrides final method OverridingFinalMethod\Foo::doFoo().',
				33,
			],
			[
				'Private method OverridingFinalMethod\Bar::doBar() overriding public method OverridingFinalMethod\Foo::doBar() should also be public.',
				38,
			],
			[
				'Protected method OverridingFinalMethod\Bar::doBaz() overriding public method OverridingFinalMethod\Foo::doBaz() should also be public.',
				43,
			],
			[
				'Private method OverridingFinalMethod\Bar::doLorem() overriding protected method OverridingFinalMethod\Foo::doLorem() should be protected or public.',
				48,
			],
		]);
	}

}
