<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Fiber;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Fiber\ExpressionAnalysisRequest;
use PHPStan\ShouldNotHappenException;
use SplObjectStorage;
use function get_class;
use function sprintf;

final class ExpressionResultStorage
{

	/** @var SplObjectStorage<Expr, Scope> */
	private SplObjectStorage $results;

	/** @var array<array{fiber: Fiber<mixed, Scope, null, ExpressionAnalysisRequest>, request: ExpressionAnalysisRequest}> */
	public array $pendingFibers = [];

	public function __construct()
	{
		$this->results = new SplObjectStorage();
	}

	public function duplicate(): self
	{
		$new = new self();
		$new->results->addAll($this->results);
		return $new;
	}

	public function storeResult(Expr $expr, Scope $scope): void
	{
		if (isset($this->results[$expr])) {
			//throw new ShouldNotHappenException(sprintf('already stored %s on line %d', get_class($expr), $expr->getStartLine()));
		}
		$this->results[$expr] = $scope;
	}

	public function findResult(Expr $expr): ?Scope
	{
		return $this->results[$expr] ?? null;
	}

}
