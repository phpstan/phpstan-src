<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExpressionResultStorageStack;
use PHPStan\Analyser\GatheringNodeCallback;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService(as: FiberNodeScopeResolver::class)]
final class FiberNodeScopeResolver extends NodeScopeResolver
{

	private ?ExpressionResultStorageStack $expressionResultStorageStack = null;

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function callNodeCallback(
		callable $nodeCallback,
		Node $node,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
	): void
	{
		// Engine-feeding gatherers must observe the node at the emission
		// position - their arrays are read as soon as the enclosing body walk
		// returns. Only the rule-facing remainder may be deferred to a fiber;
		// a rule parking on an unsettled expression must not delay gathering.
		while ($nodeCallback instanceof GatheringNodeCallback) {
			($nodeCallback->getGatherer())($node, $scope);
			$nodeCallback = $nodeCallback->getInner();
		}

		if ($nodeCallback instanceof NoopNodeCallback) {
			return;
		}

		// post-order emission means the node's own result and every subnode
		// result are already stored when the callback fires - FiberScope
		// answers every ask synchronously from the storage, so the callback
		// runs directly, no fiber to suspend
		// bind the emitting walk's storage for the duration of the callback -
		// the same association a suspended fiber's request had with the frame
		// that would resolve it
		$stack = $this->getExpressionResultStorageStack();
		$stack->push($storage);
		try {
			$nodeCallback($node, $scope->toFiberScope());
		} finally {
			$stack->pop();
		}
	}

	private function getExpressionResultStorageStack(): ExpressionResultStorageStack
	{
		return $this->expressionResultStorageStack ??= $this->container->getByType(ExpressionResultStorageStack::class);
	}

	public function storeExpressionResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $expressionResult): void
	{
		// The storage only ever answers type questions from FiberScope, which
		// resolves them from the before-scope. Storing just the before-scope
		// keeps the storage from pinning throw points, impure points, scope
		// callbacks and the after-scope of every expression until the end of
		// the file; a full result is wrapped on demand when a fiber asks.
		$storage->storeBeforeScope($expr, $expressionResult->getBeforeScope());
	}

}
