<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Fiber;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Fiber\BeforeScopeForExprRequest;
use SplObjectStorage;

final class ExpressionResultStorage
{

	/** @var SplObjectStorage<Expr, Scope> */
	private SplObjectStorage $scopes;

	/** @var array<array{fiber: Fiber<mixed, Scope, null, BeforeScopeForExprRequest>, request: BeforeScopeForExprRequest}> */
	public array $pendingFibers = [];

	public function __construct()
	{
		$this->scopes = new SplObjectStorage();
	}

	public function duplicate(): self
	{
		$new = new self();
		$new->scopes->addAll($this->scopes);
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

}
