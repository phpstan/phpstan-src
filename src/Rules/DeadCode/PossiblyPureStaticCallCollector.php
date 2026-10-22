<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;

/**
 * @implements Collector<Node\Stmt\Expression, array{class-string, string, int}>
 */
#[RegisteredCollector(level: 4)]
final class PossiblyPureStaticCallCollector implements Collector
{

	public function __construct()
	{
	}

	public function getNodeType(): string
	{
		return Expression::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		$expr = $node->expr;
		if ($expr instanceof Node\Expr\BinaryOp\Pipe) {
			if ($expr->right instanceof Node\Expr\StaticCall) {
				if (!$expr->right->isFirstClassCallable()) {
					return null;
				}

				$expr = new Node\Expr\StaticCall($expr->right->class, $expr->right->name, []);
			} elseif ($expr->right instanceof Node\Expr\ArrowFunction) {
				$expr = $expr->right->expr;
			}
		}
		if (!$expr instanceof Node\Expr\StaticCall || $expr->isFirstClassCallable()) {
			return null;
		}
		if (!$expr->name instanceof Node\Identifier) {
			return null;
		}

		if (!$expr->class instanceof Node\Name) {
			return null;
		}

		$methodName = $expr->name->toString();
		$calledOnType = $scope->resolveTypeByName($expr->class);
		$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);

		if ($methodReflection === null) {
			return null;
		}
		if (!$methodReflection->isPure()->maybe()) {
			return null;
		}
		if (!$methodReflection->hasSideEffects()->maybe()) {
			return null;
		}

		return [$methodReflection->getDeclaringClass()->getName(), $methodReflection->getName(), $node->getStartLine()];
	}

}
