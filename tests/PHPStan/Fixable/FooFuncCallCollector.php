<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<FuncCall, int>
 */
class FooFuncCallCollector implements Collector
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		if (!$node->name instanceof Name || $node->name->toLowerString() !== 'foo') {
			return null;
		}

		return $node->getStartLine();
	}

}
