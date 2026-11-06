<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Dependency\Dependencies;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * The state NodeScopeResolver threads from one statement of a statement list
 * to the next. A snapshot taken before a statement (see
 * StatementsHandler::snapshotWalkState()) is everything the walk needs to
 * continue from that statement again.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/StatementListWalkState.cpp')]
final class StatementListWalkState
{

	/** @var array<int, VariableFlow|null> */
	public array $variableFlows = [];

	public bool $alreadyTerminated = false;

	public bool $hasYield = false;

	/** @var InternalStatementExitPoint[] */
	public array $exitPoints = [];

	/** @var InternalThrowPoint[] */
	public array $throwPoints = [];

	/** @var ImpurePoint[] */
	public array $impurePoints = [];

	/** @var list<Dependencies> */
	public array $dependencies = [];

	/**
	 * Set on a snapshot: instead of lists of its own, its lists are the first
	 * ...Count entries of this state's - the walk's own state, which the walk
	 * only appends to afterwards.
	 */
	public ?StatementListWalkState $listsOf = null;

	public int $variableFlowCount = 0;

	public int $exitPointCount = 0;

	public int $throwPointCount = 0;

	public int $impurePointCount = 0;

	public int $dependencyCount = 0;

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
			variableFlow: VariableFlow::sequence(...$this->variableFlows),
			dependencies: Dependencies::merge(...$this->dependencies),
		);
	}

}
