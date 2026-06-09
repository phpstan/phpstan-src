<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Fiber;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Fiber\ExpressionResultForExprRequest;
use PHPStan\Analyser\Fiber\ParkFiberRequest;
use SplObjectStorage;

final class ExpressionResultStorage
{

	/** @var SplObjectStorage<Expr, Scope> */
	private SplObjectStorage $scopes;

	/** @var SplObjectStorage<Expr, ExpressionResult> */
	private SplObjectStorage $results;

	/** @var array<array{fiber: Fiber<mixed, ExpressionResult|array{callable(Node $node, Scope $scope): void, Node, Scope}, null, ExpressionResultForExprRequest|ParkFiberRequest>, request: ExpressionResultForExprRequest}> */
	public array $pendingFibers = [];

	/** @var list<Fiber<mixed, ExpressionResult|array{callable(Node $node, Scope $scope): void, Node, Scope}, null, ExpressionResultForExprRequest|ParkFiberRequest>> */
	public array $parkedFibers = [];

	public function __construct()
	{
		$this->scopes = new SplObjectStorage();
		$this->results = new SplObjectStorage();
	}

	public function duplicate(): self
	{
		$new = new self();
		$new->scopes->addAll($this->scopes);
		$new->results->addAll($this->results);
		return $new;
	}

	public function storeBeforeScope(Expr $expr, Scope $scope): void
	{
		$this->scopes[$expr] = $scope;
	}

	public function findBeforeScope(Expr $expr): ?Scope
	{
		return $this->scopes[$expr] ?? null;
	}

	public function storeResult(Expr $expr, ExpressionResult $result): void
	{
		$this->results[$expr] = $result;
	}

	public function findResult(Expr $expr): ?ExpressionResult
	{
		return $this->results[$expr] ?? null;
	}

}
