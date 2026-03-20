<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
final class CollectedDataEmitterRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, NodeCallbackInvoker&Scope&CollectedDataEmitter $scope): array
	{
		// same implementation as DummyCollector, but is actually a rule!
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$scope->emitCollectedData(DummyCollector::class, $node->name->toString());

		return [];
	}

}
