<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ParentStmtTypesRule>
 */
class ParentStmtTypesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ParentStmtTypesRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/parent-stmt-types.php'], [
			[
				'Parents: PhpParser\Node\Stmt\If_, PhpParser\Node\Stmt\Function_, PhpParser\Node\Stmt\Namespace_',
				11,
			],
		]);
	}

}
