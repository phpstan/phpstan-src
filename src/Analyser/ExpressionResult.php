<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class ExpressionResult
{

	private MutatingScope $scope;

	private bool $hasYield;

	/** @var ThrowPoint[] $throwPoints */
	private array $throwPoints;

	/** @var (callable(): MutatingScope)|null */
	private $truthyScopeCallback;

	private ?MutatingScope $truthyScope = null;

	/** @var (callable(): MutatingScope)|null */
	private $falseyScopeCallback;

	private ?MutatingScope $falseyScope = null;

	/**
	 * @param ThrowPoint[] $throwPoints
	 * @param (callable(): MutatingScope)|null $truthyScopeCallback
	 * @param (callable(): MutatingScope)|null $falseyScopeCallback
	 */
	public function __construct(
		MutatingScope $scope,
		bool $hasYield,
		array $throwPoints,
		?callable $truthyScopeCallback = null,
		?callable $falseyScopeCallback = null,
	)
	{
		$this->scope = $scope;
		$this->hasYield = $hasYield;
		$this->throwPoints = $throwPoints;
		$this->truthyScopeCallback = $truthyScopeCallback;
		$this->falseyScopeCallback = $falseyScopeCallback;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function hasYield(): bool
	{
		return $this->hasYield;
	}

	/**
	 * @return ThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	public function getTruthyScope(): MutatingScope
	{
		if ($this->truthyScopeCallback === null) {
			return $this->scope;
		}

		if ($this->truthyScope !== null) {
			return $this->truthyScope;
		}

		$callback = $this->truthyScopeCallback;
		$this->truthyScope = $callback();
		return $this->truthyScope;
	}

	public function getFalseyScope(): MutatingScope
	{
		if ($this->falseyScopeCallback === null) {
			return $this->scope;
		}

		if ($this->falseyScope !== null) {
			return $this->falseyScope;
		}

		$callback = $this->falseyScopeCallback;
		$this->falseyScope = $callback();
		return $this->falseyScope;
	}

}
