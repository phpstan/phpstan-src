<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use function array_map;

final class InternalStatementResult
{

	/**
	 * @param InternalStatementExitPoint[] $exitPoints
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InternalEndStatementResult[] $endStatements
	 */
	public function __construct(
		private MutatingScope $scope,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $exitPoints,
		private array $throwPoints,
		private array $impurePoints,
		private array $endStatements = [],
		private ?VariableFlow $variableFlow = null,
	)
	{
		foreach ($exitPoints as $exitPoint) {
			$this->scope = $this->scope->addTemplateArgumentConstraints($exitPoint->getScope()->getTemplateArgumentConstraints());
		}
		foreach ($endStatements as $endStatement) {
			$this->scope = $this->scope->addTemplateArgumentConstraints($endStatement->getResult()->getScope()->getTemplateArgumentConstraints());
		}
	}

	public function getVariableFlow(): ?VariableFlow
	{
		return $this->variableFlow;
	}

	public function toPublic(): StatementResult
	{
		return new StatementResult(
			$this->scope,
			$this->hasYield,
			$this->isAlwaysTerminating,
			array_map(static fn ($exitPoint) => $exitPoint->toPublic(), $this->exitPoints),
			array_map(static fn ($throwPoint) => $throwPoint->toPublic(), $this->throwPoints),
			$this->impurePoints,
			array_map(static fn ($endStatement) => $endStatement->toPublic(), $this->endStatements),
		);
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function hasYield(): bool
	{
		return $this->hasYield;
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
	}

	public function filterOutLoopExitPoints(): self
	{
		if (!$this->isAlwaysTerminating) {
			return $this;
		}

		foreach ($this->exitPoints as $exitPoint) {
			$statement = $exitPoint->getStatement();
			if (!$statement instanceof Stmt\Break_ && !$statement instanceof Stmt\Continue_) {
				continue;
			}

			$num = $statement->num;
			if (!$num instanceof Int_) {
				return new self($this->scope, $this->hasYield, false, $this->exitPoints, $this->throwPoints, $this->impurePoints, variableFlow: $this->variableFlow);
			}

			if ($num->value !== 1) {
				continue;
			}

			return new self($this->scope, $this->hasYield, false, $this->exitPoints, $this->throwPoints, $this->impurePoints, variableFlow: $this->variableFlow);
		}

		return $this;
	}

	/**
	 * @return InternalStatementExitPoint[]
	 */
	public function getExitPoints(): array
	{
		return $this->exitPoints;
	}

	/**
	 * @param class-string<Stmt\Continue_>|class-string<Stmt\Break_> $stmtClass
	 * @return list<InternalStatementExitPoint>
	 */
	public function getExitPointsByType(string $stmtClass): array
	{
		$exitPoints = [];
		foreach ($this->exitPoints as $exitPoint) {
			$statement = $exitPoint->getStatement();
			if (!$statement instanceof $stmtClass) {
				continue;
			}

			$value = $statement->num;
			if ($value === null) {
				$exitPoints[] = $exitPoint;
				continue;
			}

			if (!$value instanceof Int_) {
				$exitPoints[] = $exitPoint;
				continue;
			}

			$value = $value->value;
			if ($value !== 1) {
				continue;
			}

			$exitPoints[] = $exitPoint;
		}

		return $exitPoints;
	}

	/**
	 * @return list<InternalStatementExitPoint>
	 */
	public function getExitPointsForOuterLoop(): array
	{
		$exitPoints = [];
		foreach ($this->exitPoints as $exitPoint) {
			$statement = $exitPoint->getStatement();
			if (!$statement instanceof Stmt\Continue_ && !$statement instanceof Stmt\Break_) {
				$exitPoints[] = $exitPoint;
				continue;
			}
			if ($statement->num === null) {
				continue;
			}
			if (!$statement->num instanceof Int_) {
				continue;
			}
			$value = $statement->num->value;
			if ($value === 1) {
				continue;
			}

			$newNode = null;
			if ($value > 2) {
				$newNode = new Int_($value - 1);
			}
			if ($statement instanceof Stmt\Continue_) {
				$newStatement = new Stmt\Continue_($newNode);
			} else {
				$newStatement = new Stmt\Break_($newNode);
			}

			$exitPoints[] = new InternalStatementExitPoint($newStatement, $exitPoint->getScope());
		}

		return $exitPoints;
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	/**
	 * @return ImpurePoint[]
	 */
	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	/**
	 * Top-level StatementResult represents the state of the code
	 * at the end of control flow statements like If_ or TryCatch.
	 *
	 * It shows how Scope etc. looks like after If_ no matter
	 * which code branch was executed.
	 *
	 * For If_, "end statements" contain the state of the code
	 * at the end of each branch - if, elseifs, else, including the last
	 * statement node in each branch.
	 *
	 * For nested ifs, end statements try to contain the last non-control flow
	 * statement like Return_ or Throw_, instead of If_, TryCatch, or Foreach_.
	 *
	 * @return InternalEndStatementResult[]
	 */
	public function getEndStatements(): array
	{
		return $this->endStatements;
	}

}
