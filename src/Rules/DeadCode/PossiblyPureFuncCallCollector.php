<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Collector<Node\Stmt\Expression, array{string, int}>
 */
#[RegisteredCollector(level: 4)]
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
		$expr = $node->expr;
		if ($expr instanceof Node\Expr\BinaryOp\Pipe) {
			if ($expr->right instanceof Node\Expr\FuncCall) {
				if (!$expr->right->isFirstClassCallable()) {
					return null;
				}

				$expr = new Node\Expr\FuncCall($expr->right->name, []);
			} elseif ($expr->right instanceof Node\Expr\ArrowFunction) {
				$expr = $expr->right->expr;
			}
		}
		if (!$expr instanceof Node\Expr\FuncCall) {
			return null;
		}
		if ($expr->isFirstClassCallable()) {
			return null;
		}
		if (!$expr->name instanceof Node\Name) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
			return null;
		}

		$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
		if (!$functionReflection->isPure()->maybe()) {
			return null;
		}
		if (!$functionReflection->hasSideEffects()->maybe()) {
			return null;
		}

		return [$functionReflection->getName(), $node->getStartLine()];
	}

}
