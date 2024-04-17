<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Collector<FuncCall, array{string, int}>
 */
class PossiblyPureFuncCallCollector implements Collector
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if (!$node->name instanceof Node\Name) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return null;
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if (!$functionReflection->isPure()->maybe()) {
			return null;
		}

		return [$functionReflection->getName(), $node->getStartLine()];
	}

}
