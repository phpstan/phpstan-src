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
use function get_class;
use function get_debug_type;
use function sprintf;

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
		$fiber = new Fiber(static function () use ($node, $scope, $nodeCallback) {
			$nodeCallback($node, $scope->toFiberScope());
		});
		$request = $fiber->start();
		$this->runFiberForNodeCallback($storage, $fiber, $request);
	}

	/**
	 * @param Fiber<mixed, ExpressionResult, null, ExpressionAnalysisRequest> $fiber
	 */
	private function runFiberForNodeCallback(
		ExpressionResultStorage $storage,
		Fiber $fiber,
		?ExpressionAnalysisRequest $request,
	): void
	{
		while (!$fiber->isTerminated()) {
			if ($request instanceof ExpressionAnalysisRequest) {
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
		foreach ($storage->pendingFibers as $pending) {
			$request = $pending['request'];
			$result = $storage->findResult($request->expr);

			if ($result !== null) {
				throw new ShouldNotHappenException('Pending fibers at the end should be about synthetic nodes');
			}

			$this->processExprNode(
				new Node\Stmt\Expression($request->expr),
				$request->expr,
				$request->scope->toMutatingScope(),
				$storage,
				new NoopNodeCallback(),
				ExpressionContext::createTopLevel(),
			);
			if ($storage->findResult($request->expr) === null) {
				throw new ShouldNotHappenException(sprintf('processExprNode should have stored the result of %s on line %s', get_class($request->expr), $request->expr->getStartLine()));
			}
			$this->processPendingFibers($storage);

			// Break and restart the loop since the array may have been modified
			return;
		}
	}

	protected function processPendingFibersForRequestedExpr(ExpressionResultStorage $storage, Expr $expr, Scope $result): void
	{
		$restartLoop = true;

		while ($restartLoop) {
			$restartLoop = false;

			foreach ($storage->pendingFibers as $key => $pending) {
				$request = $pending['request'];
				if ($request->expr !== $expr) {
					continue;
				}

				unset($storage->pendingFibers[$key]);
				$restartLoop = true;

				$fiber = $pending['fiber'];
				$request = $fiber->resume($result);
				$this->runFiberForNodeCallback($storage, $fiber, $request);

				// Break and restart the loop since the array may have been modified
				break;
			}
		}
	}

}
