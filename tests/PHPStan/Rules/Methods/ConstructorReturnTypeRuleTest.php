<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ConstructorReturnTypeRule>
 */
class ConstructorReturnTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ConstructorReturnTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/constructor-return-type.php'], [
			[
				'Constructor of class ConstructorReturnType\Bar has a return type.',
				17,
			],
			[
				'Constructor of class ConstructorReturnType\UsesFooTrait has a return type.',
				26,
			],
			[
				'Original constructor of trait ConstructorReturnType\BarTrait has a return type.',
				35,
			],
		]);
	}

}
