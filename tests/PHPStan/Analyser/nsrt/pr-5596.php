<?php

namespace Pr5596;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;

use function PHPStan\Testing\assertType;

class Test
{
	private function whitelistAllowedCallables(
		CallLike $node,
		Scope $scope
	): void
	{
		if ($node instanceof MethodCall && $node->name instanceof Identifier) {
			assertType('PhpParser\Node\Expr\MethodCall', $node);
			assertType('PhpParser\Node\Identifier', $node->name);
			assertType('PhpParser\Node\Expr', $node->var);
		} elseif ($node instanceof StaticCall && $node->name instanceof Identifier && $node->class instanceof Name) {
			assertType('PhpParser\Node\Expr\StaticCall', $node);
			assertType('PhpParser\Node\Identifier', $node->name);
			assertType('PhpParser\Node\Name', $node->class);
		} elseif ($node instanceof New_ && $node->class instanceof Name) {
			assertType('PhpParser\Node\Expr\New_', $node);
			assertType('PhpParser\Node\Name', $node->class);
		} elseif ($node instanceof FuncCall && $node->name instanceof Name) {
			assertType('PhpParser\Node\Expr\FuncCall', $node);
			assertType('PhpParser\Node\Name', $node->name);
		} elseif ($node instanceof FuncCall) {
			assertType('PhpParser\Node\Expr\FuncCall', $node);
			assertType('PhpParser\Node\Expr', $node->name);
		}
	}
}
