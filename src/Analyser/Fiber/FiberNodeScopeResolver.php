<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use function array_pop;
use function count;
use function get_debug_type;
use function spl_object_id;

#[AutowiredService(as: FiberNodeScopeResolver::class)]
final class FiberNodeScopeResolver extends NodeScopeResolver
{

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
		if (Fiber::getCurrent() !== null) {
			$nodeCallback($node, $scope->toFiberScope());
			return;
		}
		if (count($storage->parkedFibers) > 0) {
			$fiber = array_pop($storage->parkedFibers);
			$request = $fiber->resume([$nodeCallback, $node, $scope]);
		} else {
			$fiber = new Fiber(static function () use ($node, $scope, $nodeCallback) {
				while (true) { // @phpstan-ignore while.alwaysTrue
					$nodeCallback($node, $scope->toFiberScope());
					[$nodeCallback, $node, $scope] = Fiber::suspend(new ParkFiberRequest());
				}
			});
			$request = $fiber->start();
		}
		$this->runFiberForNodeCallback($storage, $fiber, $request);
	}

	public function storeExpressionResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $expressionResult): void
	{
		// The storage only ever answers type questions from FiberScope, which
		// resolves them from the before-scope. Storing just the before-scope
		// keeps the storage from pinning throw points, impure points, scope
		// callbacks and the after-scope of every expression until the end of
		// the file; a full result is wrapped on demand when a fiber asks.
		$storage->storeBeforeScope($expr, $expressionResult->getBeforeScope());
		$this->processPendingFibersForRequestedExpr($storage, $expr, $expressionResult);
	}

	/**
	 * @param Fiber<mixed, ExpressionResult|array{callable(Node $node, Scope $scope): void, Node, MutatingScope}, null, ExpressionResultRequest|ParkFiberRequest> $fiber
	 */
	private function runFiberForNodeCallback(
		ExpressionResultStorage $storage,
		Fiber $fiber,
		ExpressionResultRequest|ParkFiberRequest|null $request,
	): void
	{
		while (!$fiber->isTerminated()) {
			if ($request instanceof ExpressionResultRequest) {
				$beforeScope = $storage->findBeforeScope($request->expr);
				if ($beforeScope !== null) {
					$request = $fiber->resume($this->createBeforeScopeResult($beforeScope->toMutatingScope(), $request->expr));
					continue;
				}

				$storage->pendingFibers[spl_object_id($request->expr)][] = [
					'fiber' => $fiber,
					'request' => $request,
				];
				return;
			}
			if ($request instanceof ParkFiberRequest) {
				$storage->parkedFibers[] = $fiber;
				return;
			}

			throw new ShouldNotHappenException(
				'Unknown fiber suspension: ' . get_debug_type($request),
			);
		}

		if ($request !== null) {
			throw new ShouldNotHappenException(
				'Fiber terminated but we did not handle its request ' . get_debug_type($request),
			);
		}
	}

	protected function processPendingFibers(ExpressionResultStorage $storage): void
	{
		start:

		foreach ($storage->pendingFibers as $exprId => $pendingList) {
			unset($storage->pendingFibers[$exprId]);

			foreach ($pendingList as $pending) {
				$request = $pending['request'];
				$beforeScope = $storage->findBeforeScope($request->expr);

				if ($beforeScope !== null) {
					throw new ShouldNotHappenException('Pending fibers at the end should be about synthetic nodes');
				}

				$fiber = $pending['fiber'];

				// The synthetic node was never processed in the walk, so there is
				// no stored before-scope to answer with. Resume with a result
				// anchored to the asker's own scope - its consumers resolve the
				// type on demand from the before-scope.
				$request = $fiber->resume($this->createBeforeScopeResult($request->scope->toMutatingScope(), $request->expr));
				$this->runFiberForNodeCallback($storage, $fiber, $request);
			}

			// Break and restart the loop since the resumed fibers
			// may have added new pending entries
			goto start;
		}
	}

	private function processPendingFibersForRequestedExpr(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $expressionResult): void
	{
		$exprId = spl_object_id($expr);
		while (isset($storage->pendingFibers[$exprId])) {
			$pendingList = $storage->pendingFibers[$exprId];
			unset($storage->pendingFibers[$exprId]);

			foreach ($pendingList as $pending) {
				$fiber = $pending['fiber'];
				$request = $fiber->resume($expressionResult);
				$this->runFiberForNodeCallback($storage, $fiber, $request);
			}
		}
	}

	private function createBeforeScopeResult(MutatingScope $beforeScope, Expr $expr): ExpressionResult
	{
		return $this->expressionResultFactory->create(
			$beforeScope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

}
