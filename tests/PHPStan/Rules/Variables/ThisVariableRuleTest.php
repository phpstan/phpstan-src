<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ThisVariableRule>
 */
class ThisVariableRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new ThisVariableRule();
	}

	public function testReturnTypeRule(): void
	{
		$this->analyse([__DIR__ . '/data/this.php'], [
			[
				'Using $this in static method ThisVariable\Foo::doBar().',
				16,
			],
			[
				'Using $this in static method ThisVariable\Foo::doBar().',
				20,
			],
			[
				'Using $this outside a class.',
				26,
			],
			[
				'Using $this in static method class@anonymous/tests/PHPStan/Rules/Variables/data/this.php:29::doBar().',
				38,
			],
		]);
	}

}
