<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TryCatchTypeRule>
 */
class TryCatchTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TryCatchTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/try-catch-type.php'], [
			[
				'Try catch type: nothing',
				10,
			],
			[
				'Try catch type: LogicException|RuntimeException',
				12,
			],
			[
				'Try catch type: nothing',
				14,
			],
			[
				'Try catch type: LogicException|RuntimeException|TypeError',
				17,
			],
			[
				'Try catch type: LogicException|RuntimeException',
				21,
			],
		]);
	}

}
