<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<Node\Stmt\Expression, array{non-empty-list<class-string>, string, int}>
 */
class PossiblyPureMethodCallCollector implements Collector
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
		if (!$node->expr instanceof Node\Expr\MethodCall) {
			return null;
		}
		if (!$node->expr->name instanceof Node\Identifier) {
			return null;
		}

		$methodName = $node->expr->name->toString();
		$calledOnType = $scope->getType($node->expr->var);
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
