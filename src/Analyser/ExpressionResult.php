<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Type\Type;

#[GenerateFactory(interface: ExpressionResultFactory::class)]
final class ExpressionResult
{

	/** @var (callable(): MutatingScope)|null */
	private $truthyScopeCallback;

	private ?MutatingScope $truthyScope = null;

	/** @var (callable(): MutatingScope)|null */
	private $falseyScopeCallback;

	private ?MutatingScope $falseyScope = null;

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param (callable(): MutatingScope)|null $truthyScopeCallback
	 * @param (callable(): MutatingScope)|null $falseyScopeCallback
	 */
	public function __construct(
		private MutatingScope $scope,
		private MutatingScope $beforeScope,
		private Expr $expr,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $throwPoints,
		private array $impurePoints,
		private bool $containsNullsafe = false,
		?callable $truthyScopeCallback = null,
		?callable $falseyScopeCallback = null,
	)
	{
		$this->truthyScopeCallback = $truthyScopeCallback;
		$this->falseyScopeCallback = $falseyScopeCallback;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getBeforeScope(): MutatingScope
	{
		return $this->beforeScope;
	}

	public function hasYield(): bool
	{
		return $this->hasYield;
	}

	/**
	 * Whether this expression's chain contains a nullsafe operator (?->). A
	 * fetch/call on a receiver whose chain short-circuits propagates null,
	 * which a plain nullable receiver (e.g. a nullable variable) does not -
	 * this flag is what tells them apart.
	 */
	public function containsNullsafe(): bool
	{
		return $this->containsNullsafe;
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

	public function getTruthyScope(): MutatingScope
	{
		if ($this->truthyScope !== null) {
			return $this->truthyScope;
		}

		if ($this->truthyScopeCallback === null) {
			return $this->truthyScope = $this->scope->filterByTruthyValue($this->expr);
		}

		$callback = $this->truthyScopeCallback;
		return $this->truthyScope = $callback();
	}

	public function getFalseyScope(): MutatingScope
	{
		if ($this->falseyScope !== null) {
			return $this->falseyScope;
		}

		if ($this->falseyScopeCallback === null) {
			return $this->falseyScope = $this->scope->filterByFalseyValue($this->expr);
		}

		$callback = $this->falseyScopeCallback;
		return $this->falseyScope = $callback();
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
	}

	public function getType(): Type
	{
		return $this->beforeScope->getType($this->expr);
	}

	public function getNativeType(): Type
	{
		return $this->beforeScope->getNativeType($this->expr);
	}

}
