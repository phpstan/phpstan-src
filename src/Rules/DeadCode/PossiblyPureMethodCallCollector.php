<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use function count;

/**
 * @implements Collector<Node\Stmt\Expression, array{class-string, string, int}>
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
		$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
		if ($methodReflection === null) {
			return null;
		}
		if (
			!$methodReflection->isPrivate()
			&& !$methodReflection->isFinal()->yes()
			&& !$methodReflection->getDeclaringClass()->isFinal()
		) {
			$typeClassReflections = $calledOnType->getObjectClassReflections();
			if (count($typeClassReflections) !== 1) {
				return null;
			}

			if (!$typeClassReflections[0]->isFinal()) {
				return null;
			}
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
