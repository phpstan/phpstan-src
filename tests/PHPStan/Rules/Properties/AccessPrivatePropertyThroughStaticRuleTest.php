<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<AccessPrivatePropertyThroughStaticRule> */
class AccessPrivatePropertyThroughStaticRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AccessPrivatePropertyThroughStaticRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/access-private-property-static.php'], [
			[
				'Unsafe access to private property AccessPrivatePropertyThroughStatic\Foo::$foo through static::.',
				13,
			],
		]);
	}

}
