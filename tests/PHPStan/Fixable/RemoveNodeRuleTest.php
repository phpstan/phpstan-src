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
			['Removing Stmt_Expression.', 11],
			['Removing Arg.', 13],
			['Removing Expr_FuncCall.', 14],
			['Removing Stmt_ClassMethod.', 22],
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
			$fixable[$error->getMessage()] = $error->getFixedErrorDiff() !== null;
		}

		$this->assertSame([
			'Removing Stmt_Expression.' => true,
			'Removing Arg.' => true,
			'Removing Expr_FuncCall.' => false,
			'Removing Stmt_ClassMethod.' => true,
		], $fixable);
	}

}
