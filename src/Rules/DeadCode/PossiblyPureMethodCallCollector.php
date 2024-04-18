<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use function strtolower;

/**
 * @implements Collector<Node\Stmt\Expression, list<array{class-string, string, int}>>
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
		if (strtolower($methodName) === '__construct') {
			return null;
		}

		$classType = $scope->getType($node->expr->var);
		$calls = [];
		foreach ($classType->getObjectClassReflections() as $classReflection) {
			if (!$classReflection->hasMethod($methodName)) {
				continue;
			}

			$methodReflection = $classReflection->getMethod($methodName, $scope);
			if (!$methodReflection->isPure()->maybe()) {
				return null;
			}
			if (!$methodReflection->hasSideEffects()->maybe()) {
				return null;
			}

			$calls[] = [$methodReflection->getDeclaringClass()->getName(), $methodReflection->getName(), $node->getStartLine()];
		}

		if ($calls !== []) {
			return $calls;
		}

		return null;
	}

}
