<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Collector<Node\Stmt\Expression, array{string, int}>
 */
final class PossiblyPureFuncCallCollector implements Collector
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Expression::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if (!$node->expr instanceof Node\Expr\FuncCall) {
			return null;
		}
		if (!$node->expr->name instanceof Node\Name) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($node->expr->name, $scope)) {
			return null;
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->expr->name, $scope);
		if (!$functionReflection->isPure()->maybe()) {
			return null;
		}
		if (!$functionReflection->hasSideEffects()->maybe()) {
			return null;
		}

		return [$functionReflection->getName(), $node->getStartLine()];
	}

}
