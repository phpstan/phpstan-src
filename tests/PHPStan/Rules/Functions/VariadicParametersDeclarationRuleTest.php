<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<VariadicParametersDeclarationRule>
 */
class VariadicParametersDeclarationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VariadicParametersDeclarationRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/variadic-parameters-declaration.php'], [
			[
				'Only the last parameter can be variadic.',
				7,
			],
			[
				'Only the last parameter can be variadic.',
				11,
			],
			[
				'Only the last parameter can be variadic.',
				21,
			],
		]);
	}

}
