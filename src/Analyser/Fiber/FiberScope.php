<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;
use function array_pop;
use function count;

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
		if ($node instanceof TypeExpr) {
			// Scope-independent by construction - suspending would park this
			// fiber until the end of the function because the node is never
			// visited by NodeScopeResolver, and would resolve to the same type.
			return $node->getExprType();
		}

		/** @var ExpressionResult $expressionResult */
		$expressionResult = Fiber::suspend(
			new ExpressionResultRequest($node, $this),
		);

		if (
			!$this->nativeTypesPromoted
			&& count($this->truthyValueExprs) === 0
			&& count($this->falseyValueExprs) === 0
		) {
			return $expressionResult->getType();
		}

		$scope = $this->preprocessScope($expressionResult->getBeforeScope());
		return $scope->getType($node);
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
		if ($expr instanceof TypeExpr) {
			// See getType() - same reasoning
			return $expr->getExprType();
		}

		/** @var ExpressionResult $expressionResult */
		$expressionResult = Fiber::suspend(
			new ExpressionResultRequest($expr, $this),
		);

		if (
			!$this->nativeTypesPromoted
			&& count($this->truthyValueExprs) === 0
			&& count($this->falseyValueExprs) === 0
		) {
			return $expressionResult->getNativeType();
		}

		$scope = $this->preprocessScope($expressionResult->getBeforeScope());
		return $scope->getNativeType($expr);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		/** @var ExpressionResult $expressionResult */
		$expressionResult = Fiber::suspend(
			new ExpressionResultRequest($node, $this),
		);

		$scope = $this->preprocessScope($expressionResult->getBeforeScope());

		return $scope->getKeepVoidType($node);
	}

	public function filterByTruthyValue(Expr $expr): self
	{
		/** @var self $scope */
		$scope = parent::filterByTruthyValue($expr);
		$scope->truthyValueExprs = $this->truthyValueExprs;
		$scope->falseyValueExprs = $this->falseyValueExprs;
		$scope->truthyValueExprs[] = $expr;

		return $scope;
	}

	public function filterByFalseyValue(Expr $expr): self
	{
		/** @var self $scope */
		$scope = parent::filterByFalseyValue($expr);
		$scope->truthyValueExprs = $this->truthyValueExprs;
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
