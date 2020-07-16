<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedPrivateMethodRule>
 */
class UnusedPrivateMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedPrivateMethodRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-private-method.php'], [
			[
				'Class UnusedPrivateMethod\Foo has an unused method doFoo().',
				8,
			],
			[
				'Class UnusedPrivateMethod\Foo has an unused method doBar().',
				13,
			],
			[
				'Class UnusedPrivateMethod\Foo has an unused method unusedStaticMethod().',
				44,
			],
			[
				'Class UnusedPrivateMethod\Bar has an unused method doBaz().',
				59,
			],
		]);
	}

}
