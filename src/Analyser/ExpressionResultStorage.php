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

	/** @var SplObjectStorage<Expr, ExpressionResult> */
	private SplObjectStorage $results;

	/** @var array<array{fiber: Fiber<mixed, ExpressionResult|array{callable(Node, Scope): void, Node, Scope}, null, ExpressionResultForExprRequest|ParkFiberRequest>, request: ExpressionResultForExprRequest}> */
	public array $pendingFibers = [];

	/** @var list<Fiber<mixed, ExpressionResult|array{callable(Node, Scope): void, Node, Scope}, null, ExpressionResultForExprRequest|ParkFiberRequest>> */
	public array $parkedFibers = [];

	/**
	 * Expressions currently being processed on demand by ResultAwareScope —
	 * descendants (which work on duplicates) detect ancestor cycles through this.
	 *
	 * @var array<string, true>
	 */
	public array $syntheticsInFlight = [];

	public function __construct()
	{
		$this->results = new SplObjectStorage();
	}

	public function duplicate(): self
	{
		$new = new self();
		$new->results->addAll($this->results);
		$new->syntheticsInFlight = $this->syntheticsInFlight;
		return $new;
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
