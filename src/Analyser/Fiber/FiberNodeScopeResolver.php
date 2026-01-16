<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
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
	protected function callNodeCallback(
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

	protected function storeBeforeScope(ExpressionResultStorage $storage, Expr $expr, Scope $beforeScope): void
	{
		$storage->storeBeforeScope($expr, $beforeScope);
		$this->processPendingFibersForRequestedExpr($storage, $expr, $beforeScope);
	}

	/**
	 * @param Fiber<mixed, Scope|array{callable(Node $node, Scope $scope): void, Node, Scope}, null, BeforeScopeForExprRequest|ParkFiberRequest> $fiber
	 */
	private function runFiberForNodeCallback(
		ExpressionResultStorage $storage,
		Fiber $fiber,
		BeforeScopeForExprRequest|ParkFiberRequest|null $request,
	): void
	{
		while (!$fiber->isTerminated()) {
			if ($request instanceof BeforeScopeForExprRequest) {
				$beforeScope = $storage->findBeforeScope($request->expr);
				if ($beforeScope !== null) {
					$request = $fiber->resume($beforeScope);
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
			$beforeScope = $storage->findBeforeScope($request->expr);

			if ($beforeScope !== null) {
				throw new ShouldNotHappenException('Pending fibers at the end should be about synthetic nodes');
			}

			unset($storage->pendingFibers[$key]);

			$fiber = $pending['fiber'];
			$request = $fiber->resume($request->scope);
			$this->runFiberForNodeCallback($storage, $fiber, $request);

			// Break and restart the loop since the array may have been modified
			goto start;
		}
	}

	private function processPendingFibersForRequestedExpr(ExpressionResultStorage $storage, Expr $expr, Scope $result): void
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
