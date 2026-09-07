<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * The state NodeScopeResolver threads from one statement of a statement list
 * to the next. A snapshot (clone) taken before a statement is everything the
 * walk needs to continue from that statement again.
 */
final class StatementListWalkState
{

	public bool $alreadyTerminated = false;

	public bool $hasYield = false;

	/** @var InternalStatementExitPoint[] */
	public array $exitPoints = [];

	/** @var InternalThrowPoint[] */
	public array $throwPoints = [];

	/** @var ImpurePoint[] */
	public array $impurePoints = [];

	public function __construct(public MutatingScope $scope)
	{
	}

	public function toResult(): InternalStatementResult
	{
		return new InternalStatementResult(
			$this->scope,
			$this->hasYield,
			$this->alreadyTerminated,
			$this->exitPoints,
			$this->throwPoints,
			$this->impurePoints,
		);
	}

}
