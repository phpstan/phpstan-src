<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReturnStatementsNodeSyntheticAskRule>
 */
class ReturnStatementsNodeSyntheticAskRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReturnStatementsNodeSyntheticAskRule();
	}

	public function testSyntheticAskFromReturnStatementsNode(): void
	{
		$this->analyse([__DIR__ . '/data/return-statements-synthetic-ask.php'], [
			[
				'function: 3',
				5,
			],
			[
				'method: 3',
				13,
			],
		]);
	}

}
