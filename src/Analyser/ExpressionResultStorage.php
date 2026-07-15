<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Fiber;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Fiber\BeforeScopeForExprRequest;
use PHPStan\Analyser\Fiber\ParkFiberRequest;
use function spl_object_id;

final class ExpressionResultStorage
{

	/**
	 * Keeps every stored Expr alive so its spl_object_id() cannot be reused
	 * by another node while $scopesById still maps it.
	 *
	 * @var array<int, Expr>
	 */
	private array $exprsById = [];

	/** @var array<int, Scope> */
	private array $scopesById = [];

	/** @var array<array{fiber: Fiber<mixed, Scope|array{callable(Node $node, Scope $scope): void, Node, Scope}, null, BeforeScopeForExprRequest|ParkFiberRequest>, request: BeforeScopeForExprRequest}> */
	public array $pendingFibers = [];

	/** @var list<Fiber<mixed, Scope|array{callable(Node $node, Scope $scope): void, Node, Scope}, null, BeforeScopeForExprRequest|ParkFiberRequest>> */
	public array $parkedFibers = [];

	public function duplicate(): self
	{
		$new = new self();
		$new->exprsById = $this->exprsById;
		$new->scopesById = $this->scopesById;
		return $new;
	}

	public function storeBeforeScope(Expr $expr, Scope $scope): void
	{
		$id = spl_object_id($expr);
		$this->exprsById[$id] = $expr;
		$this->scopesById[$id] = $scope;
	}

	public function findBeforeScope(Expr $expr): ?Scope
	{
		return $this->scopesById[spl_object_id($expr)] ?? null;
	}

}
