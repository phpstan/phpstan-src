<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;
use function array_pop;

final class FiberScope extends MutatingScope
{

	/** @var Expr[] */
	private array $truthyValueExprs = [];

	/** @var Expr[] */
	private array $falseyValueExprs = [];

	private ?MutatingScope $mutatingScope = null;

	public function toFiberScope(): self
	{
		return $this;
	}

	public function toMutatingScope(): MutatingScope
	{
		if ($this->mutatingScope !== null) {
			return $this->mutatingScope;
		}

		return $this->mutatingScope = $this->scopeFactory->toMutatingFactory()->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->getAnonymousFunctionReflection(),
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->getParentScope(),
			$this->nativeTypesPromoted,
		);
	}

	/** @api */
	public function getType(Expr $node): Type
	{
		/** @var ExpressionResult $result */
		$result = Fiber::suspend(
			new ExpressionResultForExprRequest($node, $this),
		);

		return $result->getTypeForScope($this);
	}

	public function getScopeType(Expr $expr): Type
	{
		return $this->toMutatingScope()->getType($expr);
	}

	public function getScopeNativeType(Expr $expr): Type
	{
		return $this->toMutatingScope()->getNativeType($expr);
	}

	/** @api */
	public function getNativeType(Expr $expr): Type
	{
		/** @var ExpressionResult $result */
		$result = Fiber::suspend(
			new ExpressionResultForExprRequest($expr, $this),
		);

		return $result->getNativeType();
	}

	public function getKeepVoidType(Expr $node): Type
	{
		/** @var ExpressionResult $result */
		$result = Fiber::suspend(
			new ExpressionResultForExprRequest($node, $this),
		);

		// keepVoid is a one-off we will solve separately; fall back to the regular type for now.
		return $result->getTypeForScope($this);
	}

	public function filterByTruthyValue(Expr $expr): self
	{
		/** @var self $scope */
		$scope = parent::filterByTruthyValue($expr);
		$scope->truthyValueExprs = $this->truthyValueExprs;
		$scope->truthyValueExprs[] = $expr;

		return $scope;
	}

	public function filterByFalseyValue(Expr $expr): self
	{
		/** @var self $scope */
		$scope = parent::filterByTruthyValue($expr);
		$scope->falseyValueExprs = $this->falseyValueExprs;
		$scope->falseyValueExprs[] = $expr;

		return $scope;
	}

	private function preprocessScope(MutatingScope $scope): Scope
	{
		if ($this->nativeTypesPromoted) {
			$scope = $scope->doNotTreatPhpDocTypesAsCertain();
		}

		foreach ($this->truthyValueExprs as $expr) {
			$scope = $scope->filterByTruthyValue($expr);
		}
		foreach ($this->falseyValueExprs as $expr) {
			$scope = $scope->filterByFalseyValue($expr);
		}

		return $scope;
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $reflection
	 */
	public function pushInFunctionCall($reflection, ?ParameterReflection $parameter, bool $rememberTypes): self
	{
		/** @var self $scope */
		$scope = parent::pushInFunctionCall($reflection, $parameter, $rememberTypes);
		$scope->truthyValueExprs = $this->truthyValueExprs;
		$scope->falseyValueExprs = $this->falseyValueExprs;

		return $scope;
	}

	public function popInFunctionCall(): self
	{
		$stack = $this->inFunctionCallsStack;
		array_pop($stack);

		/** @var self $scope */
		$scope = parent::popInFunctionCall();
		$scope->truthyValueExprs = $this->truthyValueExprs;
		$scope->falseyValueExprs = $this->falseyValueExprs;

		return $scope;
	}

	public function getParentScope(): ?MutatingScope
	{
		$parent = parent::getParentScope();
		if ($parent === null) {
			return null;
		}

		return $parent->toFiberScope();
	}

}
