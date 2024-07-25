<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<Node\Stmt\Expression, array{class-string, string, int}>
 */
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
		if (!$node->expr instanceof Node\Expr\StaticCall) {
			return null;
		}
		if (!$node->expr->name instanceof Node\Identifier) {
			return null;
		}

		if (!$node->expr->class instanceof Node\Name) {
			return null;
		}

		$methodName = $node->expr->name->toString();
		$calledOnType = $scope->resolveTypeByName($node->expr->class);
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
