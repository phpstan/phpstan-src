<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Fiber;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Fiber\ExpressionResultRequest;
use PHPStan\Analyser\Fiber\ParkFiberRequest;
use PHPStan\Turbo\ShadowedByTurboExtension;
use SplObjectStorage;

#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\ExpressionResultStorage', implementation: __DIR__ . '/../../turbo-ext/src/ExpressionResultStorage.cpp')]
final class ExpressionResultStorage
{

	/** @var SplObjectStorage<Expr, ExpressionResult> */
	private SplObjectStorage $exprResults;

	/**
	 * Read-only fallback - writes never reach it. Makes duplicate() O(1)
	 * instead of copying all stored results.
	 */
	private ?self $fallback = null;

	/**
	 * Keyed by spl_object_id() of the requested Expr, so resolving a stored
	 * expression result touches only the fibers waiting for that expression.
	 * The request object keeps the Expr alive, so its id cannot be reused
	 * while the entry exists.
	 *
	 * @var array<int, non-empty-list<array{fiber: Fiber<mixed, ExpressionResult|array{callable(Node $node, Scope $scope): void, Node, MutatingScope}, null, ExpressionResultRequest|ParkFiberRequest>, request: ExpressionResultRequest}>>
	 */
	public array $pendingFibers = [];

	/** @var list<Fiber<mixed, ExpressionResult|array{callable(Node $node, Scope $scope): void, Node, MutatingScope}, null, ExpressionResultRequest|ParkFiberRequest>> */
	public array $parkedFibers = [];

	public function __construct()
	{
		$this->exprResults = new SplObjectStorage();
	}

	public function duplicate(): self
	{
		$new = new self();
		$new->fallback = $this;
		return $new;
	}

	public function mergeResults(self $other): void
	{
		$this->exprResults->addAll($other->exprResults);
	}

	public function storeExpressionResult(Expr $expr, ExpressionResult $expressionResult): void
	{
		$this->exprResults[$expr] = $expressionResult;
	}

	public function findExpressionResult(Expr $expr): ?ExpressionResult
	{
		return $this->exprResults[$expr] ?? ($this->fallback !== null ? $this->fallback->findExpressionResult($expr) : null);
	}

}
