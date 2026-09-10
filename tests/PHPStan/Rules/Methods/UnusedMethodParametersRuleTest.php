<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedMethodParametersRule>
 */
class UnusedMethodParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedMethodParametersRule(self::getContainer()->getByType(UnusedParametersCheck::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-method-parameters.php'], [
			[
				'Method UnusedMethodParameters\Foo::completelyUnused() has an unused parameter $unused.',
				24,
			],
			[
				'Method UnusedMethodParameters\Foo::immediatelyReassigned() has an unused parameter $x.',
				29,
			],
			[
				'Method UnusedMethodParameters\Foo::byRefUnused() has an unused parameter $x.',
				57,
			],
			[
				'Method UnusedMethodParameters\Foo::staticCompletelyUnused() has an unused parameter $unused.',
				69,
			],
		]);
	}

}
