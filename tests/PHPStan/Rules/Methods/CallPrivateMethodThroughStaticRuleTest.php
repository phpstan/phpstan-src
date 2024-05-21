<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallPrivateMethodThroughStaticRule>
 */
class CallPrivateMethodThroughStaticRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallPrivateMethodThroughStaticRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-private-method-static.php'], [
			[
				'Unsafe call to private method CallPrivateMethodThroughStatic\Foo::doBar() through static::.',
				12,
			],
		]);
	}

	public function testInstanceof(): void
	{
		$this->analyse([__DIR__ . '/data/call-private-method-static-instanceof.php'], [
			[
				'Unsafe call to private method CallPrivateMethodStaticInstanceof\FooBase::fooPrivate() through static::.',
				27,
			],
		]);
	}

}
