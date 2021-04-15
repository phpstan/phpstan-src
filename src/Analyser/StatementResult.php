<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt;

class StatementResult
{

	private Stmt $statement;

	private MutatingScope $scope;

	private bool $hasYield;

	private bool $isAlwaysTerminating;

	/** @var StatementExitPoint[] */
	private array $exitPoints;

	/** @var ThrowPoint[] */
	private array $throwPoints;

	/**
	 * @param Stmt $statement
	 * @param MutatingScope $scope
	 * @param bool $hasYield
	 * @param bool $isAlwaysTerminating
	 * @param StatementExitPoint[] $exitPoints
	 * @param ThrowPoint[] $throwPoints
	 */
	public function __construct(
		Stmt $statement,
		MutatingScope $scope,
		bool $hasYield,
		bool $isAlwaysTerminating,
		array $exitPoints,
		array $throwPoints
	)
	{
		$this->statement = $statement;
		$this->scope = $scope;
		$this->hasYield = $hasYield;
		$this->isAlwaysTerminating = $isAlwaysTerminating;
		$this->exitPoints = $exitPoints;
		$this->throwPoints = $this->filterThrowPoints($statement, $throwPoints);
	}

	/**
	 * @param Stmt $statement
	 * @param ThrowPoint[] $throwPoints
	 * @return ThrowPoint[]
	 */
	private function filterThrowPoints(Stmt $statement, array $throwPoints): array
	{
		if ($this->isInTry($statement)) {
			return $throwPoints;
		}

		return array_values(array_filter($throwPoints, static function (ThrowPoint $throwPoint): bool {
			return $throwPoint->isExplicit();
		}));
	}

	private function isInTry(\PhpParser\Node $node): bool
	{
		if ($node instanceof Stmt\TryCatch) {
			return true;
		}

		if ($node instanceof Stmt\Catch_ || $node instanceof Stmt\Finally_) {
			$parent = $node->getAttribute('parent');
			if ($parent === null) {
				return false;
			}
			$parent2 = $parent->getAttribute('parent');
			if (!$parent2 instanceof Stmt) {
				return false;
			}
			return $this->isInTry($parent2);
		}

		if (
			$node instanceof Stmt\ClassMethod
			|| $node instanceof Stmt\Function_
			|| $node instanceof Closure
		) {
			return false;
		}

		$parent = $node->getAttribute('parent');
		if ($parent === null) {
			return false;
		}

		return $this->isInTry($parent);
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
				return new self($this->statement, $this->scope, $this->hasYield, false, $this->exitPoints, $this->throwPoints);
			}

			if ($num->value !== 1) {
				continue;
			}

			return new self($this->statement, $this->scope, $this->hasYield, false, $this->exitPoints, $this->throwPoints);
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

}
