<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AccessPrivateConstantThroughStaticRule>
 */
class AccessPrivateConstantThroughStaticRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AccessPrivateConstantThroughStaticRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/access-private-constant-static.php'], [
			[
				'Unsafe access to private constant AccessPrivateConstantThroughStatic\Foo::FOO through static::.',
				12,
			],
		]);
	}

}
