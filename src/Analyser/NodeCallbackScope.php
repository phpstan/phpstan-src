<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;
use WeakReference;
use function array_pop;
use function count;

final class NodeCallbackScope extends MutatingScope
{

	/** @var Expr[] */
	private array $truthyValueExprs = [];

	/** @var Expr[] */
	private array $falseyValueExprs = [];

	private ?MutatingScope $mutatingScope = null;

	/** @var WeakReference<MutatingScope>|null */
	private ?WeakReference $seededMutatingScope = null;

	public function toNodeCallbackScope(): self
	{
		return $this;
	}

	/**
	 * Called by MutatingScope::toNodeCallbackScope() with the scope this one was
	 * created from: same state, so it can answer toMutatingScope() directly -
	 * keeping its resolvedTypes memo and the identity with stored results'
	 * beforeScope that askScopeVariableStateMatches() short-circuits on.
	 * Weakly referenced: the origin caches this scope in its $nodeCallbackScope, a
	 * strong back-reference would cycle and never free with GC disabled.
	 */
	public function seedMutatingScope(MutatingScope $scope): void
	{
		$this->seededMutatingScope = WeakReference::create($scope);
	}

	public function toMutatingScope(): MutatingScope
	{
		if ($this->mutatingScope !== null) {
			return $this->mutatingScope;
		}

		if ($this->seededMutatingScope !== null) {
			$seeded = $this->seededMutatingScope->get();
			if ($seeded !== null) {
				return $seeded;
			}
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
			// scope-independent by construction
			return $node->getExprType();
		}

		if (
			!$this->nativeTypesPromoted
			&& count($this->truthyValueExprs) === 0
			&& count($this->falseyValueExprs) === 0
		) {
			$storedResult = $this->findSettledStoredResult($node);
			if ($storedResult !== null) {
				return $this->getStoredResultTypeOnThisScope($storedResult, $node, false);
			}

			// post-order emission means every real subnode is already stored -
			// an unstored ask is a synthetic node or a node ahead of the walk,
			// answered on demand through the MutatingScope path
			return $this->toMutatingScope()->getType($node);
		}

		$storedResult = $this->findSettledStoredResult($node);
		if ($storedResult !== null) {
			$scope = $this->preprocessScope($storedResult->getBeforeScope());
			return $scope->getType($node);
		}

		// the filters/promotion already narrowed this scope's own tables
		return $this->toMutatingScope()->getType($node);
	}

	/**
	 * Consumes a stored result guarded by this scope's position instead of
	 * reading the naked walk-position type: a callback may have derived this
	 * scope (e.g. assignExpression() pinning a call-site literal onto a
	 * parameter) and the walk-position memo predates that. On a state match
	 * the result's own read is the answer; a counterfactual ask re-prices on
	 * the MutatingScope, mirroring resolveTypeOfNewWorldHandlerNode().
	 */
	private function getStoredResultTypeOnThisScope(ExpressionResult $result, Expr $node, bool $useNativeTypes): Type
	{
		$scope = $this->toMutatingScope();
		if ($result->canResolveOwnType() && $result->askScopeVariableStateMatches($scope, $useNativeTypes, true)) {
			return $useNativeTypes ? $result->getNativeType() : $result->getType();
		}

		return $useNativeTypes ? $scope->getNativeType($node) : $scope->getType($node);
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

		if (
			!$this->nativeTypesPromoted
			&& count($this->truthyValueExprs) === 0
			&& count($this->falseyValueExprs) === 0
		) {
			$storedResult = $this->findSettledStoredResult($expr);
			if ($storedResult !== null) {
				return $this->getStoredResultTypeOnThisScope($storedResult, $expr, true);
			}

			return $this->toMutatingScope()->getNativeType($expr);
		}

		$storedResult = $this->findSettledStoredResult($expr);
		if ($storedResult !== null) {
			$scope = $this->preprocessScope($storedResult->getBeforeScope());
			return $scope->getNativeType($expr);
		}

		return $this->toMutatingScope()->getNativeType($expr);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		$storedResult = $this->findSettledStoredResult($node);
		if ($storedResult !== null) {
			$scope = $this->preprocessScope($storedResult->getBeforeScope());

			return $scope->getKeepVoidType($node);
		}

		return $this->toMutatingScope()->getKeepVoidType($node);
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
		// a nested walk a rule started from its NodeCallbackScope may have
		// anchored results to callback scopes - re-entering this class's ask
		// paths from here would derive scopes without end
		$scope = $scope->toMutatingScope();
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

		return $parent->toNodeCallbackScope();
	}

}
