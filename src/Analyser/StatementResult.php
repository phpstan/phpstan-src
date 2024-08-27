<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt;

/**
 * @api
 * @final
 */
class StatementResult
{

	/**
	 * @param StatementExitPoint[] $exitPoints
	 * @param ThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param EndStatementResult[] $endStatements
	 */
	public function __construct(
		private MutatingScope $scope,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $exitPoints,
		private array $throwPoints,
		private array $impurePoints,
		private array $endStatements = [],
	)
	{
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
			if (!$num instanceof LNumber) {
				return new self($this->scope, $this->hasYield, false, $this->exitPoints, $this->throwPoints, $this->impurePoints);
			}

			if ($num->value !== 1) {
				continue;
			}

			return new self($this->scope, $this->hasYield, false, $this->exitPoints, $this->throwPoints, $this->impurePoints);
		}

		return $this;
	}

	/**
	 * @return StatementExitPoint[]
	 */
	public function getExitPoints(): array
	{
		return $this->exitPoints;
	}

	/**
	 * @param class-string<Stmt\Continue_>|class-string<Stmt\Break_> $stmtClass
	 * @return StatementExitPoint[]
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

			if (!$value instanceof LNumber) {
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
	 * @return StatementExitPoint[]
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
			if (!$statement->num instanceof LNumber) {
				continue;
			}
			$value = $statement->num->value;
			if ($value === 1) {
				continue;
			}

			$newNode = null;
			if ($value > 2) {
				$newNode = new LNumber($value - 1);
			}
			if ($statement instanceof Stmt\Continue_) {
				$newStatement = new Stmt\Continue_($newNode);
			} else {
				$newStatement = new Stmt\Break_($newNode);
			}

			$exitPoints[] = new StatementExitPoint($newStatement, $exitPoint->getScope());
		}

		return $exitPoints;
	}

	/**
	 * @return ThrowPoint[]
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
	 * @return EndStatementResult[]
	 */
	public function getEndStatements(): array
	{
		return $this->endStatements;
	}

}
