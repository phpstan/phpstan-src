<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<Node\Expr\MethodCall, never>
 */
final class CollectedDataEmitterCollector implements Collector
{

	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope&CollectedDataEmitter $scope)
	{
		// same data as DummyCollector, but emitted for it by another collector
		if (!$node->name instanceof Node\Identifier) {
			return null;
		}

		$scope->emitCollectedData(DummyCollector::class, $node->name->toString());

		return null;
	}

}
