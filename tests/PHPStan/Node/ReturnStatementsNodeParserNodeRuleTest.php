<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReturnStatementsNodeParserNodeRule>
 */
class ReturnStatementsNodeParserNodeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReturnStatementsNodeParserNodeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/return-statements-node-parser-node.php'], [
			[
				'Stmt_ClassMethod doFoo(), lines 8-11, return type node: NullableType',
				8,
			],
			[
				'Stmt_ClassMethod doBar(), lines 13-16, return type node: none',
				13,
			],
			[
				'Stmt_Function doBaz(), lines 20-23, return type node: Identifier',
				20,
			],
		]);
	}

}
