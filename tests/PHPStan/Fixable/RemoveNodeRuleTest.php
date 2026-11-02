<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RemoveNodeRule>
 */
class RemoveNodeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RemoveNodeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/remove-node.php'], [
			['Removing items.', 8],
			['Removing items.', 10],
			['Removing items.', 12],
			['Removing items.', 14],
			['Removing Stmt_Expression.', 19],
			['Removing Arg.', 21],
			['Removing Expr_FuncCall.', 22],
			['Removing Stmt_ClassMethod.', 30],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/remove-node.php', __DIR__ . '/data/remove-node.php.fixed');
	}

	public function testNodeNotInArrayIsNotFixable(): void
	{
		$fixable = [];
		foreach ($this->gatherAnalyserErrors([__DIR__ . '/data/remove-node.php']) as $error) {
			$fixable[$error->getMessage()] = $error->getFixedErrorDiffs() !== [];
		}

		$this->assertSame([
			'Removing items.' => true,
			'Removing Stmt_Expression.' => true,
			'Removing Arg.' => true,
			'Removing Expr_FuncCall.' => false,
			'Removing Stmt_ClassMethod.' => true,
		], $fixable);
	}

}
