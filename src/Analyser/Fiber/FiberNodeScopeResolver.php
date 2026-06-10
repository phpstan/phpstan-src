<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use function array_pop;
use function count;
use function get_debug_type;

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
		if ($nodeCallback instanceof NoopNodeCallback) {
			return;
		}

		if (Fiber::getCurrent() !== null) {
			$nodeCallback($node, $scope->toFiberScope());
			return;
		}
		if (count($storage->parkedFibers) > 0) {
			$fiber = array_pop($storage->parkedFibers);
			if ($fiber === null) {
				throw new ShouldNotHappenException();
			}
			$request = $fiber->resume([$nodeCallback, $node, $scope]);
		} else {
			/** @var Fiber<mixed, ExpressionResult|array{callable(Node, Scope): void, Node, Scope}, null, ExpressionResultForExprRequest|ParkFiberRequest> $fiber */
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

	public function storeResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $result): void
	{
		parent::storeResult($storage, $expr, $result);
		$this->processPendingFibersForRequestedExpr($storage, $expr, $result);
	}

	/**
	 * @param Fiber<mixed, ExpressionResult|array{callable(Node, Scope): void, Node, Scope}, null, ExpressionResultForExprRequest|ParkFiberRequest> $fiber
	 */
	private function runFiberForNodeCallback(
		ExpressionResultStorage $storage,
		Fiber $fiber,
		ExpressionResultForExprRequest|ParkFiberRequest|null $request,
	): void
	{
		while (!$fiber->isTerminated()) {
			if ($request instanceof ExpressionResultForExprRequest) {
				$result = $storage->findResult($request->expr);
				if ($result !== null) {
					$request = $fiber->resume($result);
					continue;
				}

				$storage->pendingFibers[] = [
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

		foreach ($storage->pendingFibers as $key => $pending) {
			$request = $pending['request'];
			if ($storage->findResult($request->expr) !== null) {
				throw new ShouldNotHappenException('Pending fibers at the end should be about synthetic nodes');
			}

			unset($storage->pendingFibers[$key]);

			// Synthetic node: never visited by traversal, so produce its ExpressionResult now
			// on the scope captured at suspension time.
			$result = $this->processExprNode(
				new Node\Stmt\Expression($request->expr),
				$request->expr,
				// process on the plain scope — a FiberScope would suspend from within
				$request->scope->toMutatingScope(),
				$storage,
				static function (): void {
				},
				ExpressionContext::createDeep(),
			);

			$fiber = $pending['fiber'];
			$nextRequest = $fiber->resume($result);
			$this->runFiberForNodeCallback($storage, $fiber, $nextRequest);

			// Break and restart the loop since the array may have been modified
			goto start;
		}
	}

	private function processPendingFibersForRequestedExpr(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $result): void
	{
		start:

		foreach ($storage->pendingFibers as $key => $pending) {
			$request = $pending['request'];
			if ($request->expr !== $expr) {
				continue;
			}

			unset($storage->pendingFibers[$key]);

			$fiber = $pending['fiber'];
			$request = $fiber->resume($result);
			$this->runFiberForNodeCallback($storage, $fiber, $request);

			// Break and restart the loop since the array may have been modified
			goto start;
		}
	}

}
