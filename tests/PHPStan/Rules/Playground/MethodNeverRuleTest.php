<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MethodNeverRule>
 */
class MethodNeverRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MethodNeverRule(new NeverRuleHelper());
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-never.php'], [
			[
				'Method MethodNever\Foo::doBar() always throws an exception, it should have return type "never".',
				21,
			],
			[
				'Method MethodNever\Foo::callsNever() always terminates script execution, it should have return type "never".',
				26,
			],
			[
				'Method MethodNever\Foo::doBaz() always terminates script execution, it should have return type "never".',
				31,
			],
		]);
	}

}
