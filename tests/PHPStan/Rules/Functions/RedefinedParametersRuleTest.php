<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RedefinedParametersRule>
 */
class RedefinedParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RedefinedParametersRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/redefined-parameters.php'], [
			[
				'Redefinition of parameter $foo.',
				11,
			],
			[
				'Redefinition of parameter $bar.',
				13,
			],
			[
				'Redefinition of parameter $baz.',
				15,
			],
		]);
	}

}
