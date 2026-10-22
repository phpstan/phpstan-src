<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;

/**
 * @implements Collector<Node\Stmt\Expression, array{non-empty-list<class-string>, string, int}>
 */
#[RegisteredCollector(level: 4)]
final class PossiblyPureMethodCallCollector implements Collector
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
			if ($expr->right instanceof Node\Expr\MethodCall || $expr->right instanceof Node\Expr\NullsafeMethodCall) {
				if (!$expr->right->isFirstClassCallable()) {
					return null;
				}

				$expr = new Node\Expr\MethodCall($expr->right->var, $expr->right->name, []);
			} elseif ($expr->right instanceof Node\Expr\ArrowFunction) {
				$expr = $expr->right->expr;
			}
		}

		if (!$expr instanceof Node\Expr\MethodCall && !$expr instanceof Node\Expr\NullsafeMethodCall) {
			return null;
		}
		if ($expr->isFirstClassCallable()) {
			return null;
		}
		if (!$expr->name instanceof Node\Identifier) {
			return null;
		}

		$methodName = $expr->name->toString();
		$calledOnType = $scope->getType($expr->var);
		if (!$calledOnType->hasMethod($methodName)->yes()) {
			return null;
		}

		$classNames = [];
		$methodReflection = null;
		foreach ($calledOnType->getObjectClassReflections() as $classReflection) {
			if (!$classReflection->hasMethod($methodName)) {
				return null;
			}

			$methodReflection = $classReflection->getMethod($methodName, $scope);
			if (
				!$methodReflection->isPrivate()
				&& !$methodReflection->isFinal()->yes()
				&& !$methodReflection->getDeclaringClass()->isFinal()
			) {
				if (!$classReflection->isFinal()) {
					return null;
				}
			}
			if (!$methodReflection->isPure()->maybe()) {
				return null;
			}
			if (!$methodReflection->hasSideEffects()->maybe()) {
				return null;
			}

			$classNames[] = $methodReflection->getDeclaringClass()->getName();
		}

		if ($methodReflection === null) {
			return null;
		}

		return [$classNames, $methodReflection->getName(), $node->getStartLine()];
	}

}
