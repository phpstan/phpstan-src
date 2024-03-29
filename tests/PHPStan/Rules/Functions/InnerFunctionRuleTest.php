<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InnerFunctionRule>
 */
class InnerFunctionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InnerFunctionRule();
	}

	public function testInnerFunction(): void
	{
		$this->analyse([__DIR__ . '/data/inner-functions.php'], [
			[
				'Inner named functions are not supported by PHPStan. Consider refactoring to an anonymous function, class method, or a top-level-defined function. See issue #165 (https://github.com/phpstan/phpstan/issues/165) for more details.',
				7,
			],
			[
				'Inner named functions are not supported by PHPStan. Consider refactoring to an anonymous function, class method, or a top-level-defined function. See issue #165 (https://github.com/phpstan/phpstan/issues/165) for more details.',
				18,
			],
		]);
	}

}
