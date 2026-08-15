<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;
use function array_pop;
use function count;
use function spl_object_id;

final class NodeCallbackScope extends MutatingScope
{

	/** @var Expr[] */
	private array $truthyValueExprs = [];

	/** @var Expr[] */
	private array $falseyValueExprs = [];

	private ?MutatingScope $walkScope = null;

	/**
	 * The storage of the emitting walk, pushed by callNodeCallback() for the
	 * duration of the callback - the same association a suspended fiber's
	 * request had with the frame that would resolve it. Resolved through the
	 * container so the scope never references a storage directly (a direct
	 * reference would cycle with the storage's stored scopes and never free
	 * with the cycle collector disabled).
	 */
	private function findStoredBeforeScope(Expr $expr): ?MutatingScope
	{
		$storage = $this->container->getByType(ExpressionResultStorageStack::class)->getCurrent();
		if ($storage === null) {
			return null;
		}

		$beforeScope = $storage->findBeforeScope($expr);
		if ($beforeScope instanceof MutatingScope) {
			return $beforeScope;
		}

		return null;
	}

	public function toNodeCallbackScope(): self
	{
		return $this;
	}

	public function toWalkScope(): MutatingScope
	{
		if ($this->walkScope !== null) {
			return $this->walkScope;
		}

		return $this->walkScope = $this->scopeFactory->toWalkScopeFactory()->create(
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

	/**
	 * Asked types memoized by node identity. Rules and collectors re-ask the
	 * same nodes across a callback batch (this scope is shared by every
	 * callback fired at the emission point), and the walk scope's own
	 * resolvedTypes memo answered those in O(1) before this class existed -
	 * without this, every repeat pays the stored-result lookup again. The
	 * entry keeps the node itself: a synthetic node a rule dropped can hand
	 * its object id to the next synthetic, and the identity check rejects
	 * such stale hits.
	 *
	 * @var array<int, array{Expr, Type}>
	 */
	private array $askedTypes = [];

	/** @var array<int, array{Expr, Type}> */
	private array $askedNativeTypes = [];

	/** @api */
	public function getType(Expr $node): Type
	{
		if ($node instanceof TypeExpr) {
			// Scope-independent by construction - suspending would park this
			// fiber until the end of the function because the node is never
			// visited by NodeScopeResolver, and would resolve to the same type.
			return $node->getExprType();
		}

		$nodeId = spl_object_id($node);
		if (isset($this->askedTypes[$nodeId]) && $this->askedTypes[$nodeId][0] === $node) {
			return $this->askedTypes[$nodeId][1];
		}

		$type = $this->doGetType($node);
		$this->askedTypes[$nodeId] = [$node, $type];

		return $type;
	}

	private function doGetType(Expr $node): Type
	{
		// post-order emission means the node's own result and every subnode
		// result are already stored when the callback fires - answer from the
		// stored before-scope; an unstored ask is a synthetic node or a node
		// ahead of the walk, answered on demand through the MutatingScope path
		// (the same answer the fiber flush produced for a never-stored ask)
		$beforeScope = $this->findStoredBeforeScope($node);

		if (
			!$this->nativeTypesPromoted
			&& count($this->truthyValueExprs) === 0
			&& count($this->falseyValueExprs) === 0
		) {
			if ($beforeScope !== null) {
				return $beforeScope->getType($node);
			}

			return $this->toWalkScope()->getType($node);
		}

		$scope = $this->preprocessScope($beforeScope ?? $this->toWalkScope());
		return $scope->getType($node);
	}

	public function getScopeType(Expr $expr): Type
	{
		return $this->toWalkScope()->getType($expr);
	}

	public function getScopeNativeType(Expr $expr): Type
	{
		return $this->toWalkScope()->getNativeType($expr);
	}

	/** @api */
	public function getNativeType(Expr $expr): Type
	{
		if ($expr instanceof TypeExpr) {
			// See getType() - same reasoning
			return $expr->getExprType();
		}

		$nodeId = spl_object_id($expr);
		if (isset($this->askedNativeTypes[$nodeId]) && $this->askedNativeTypes[$nodeId][0] === $expr) {
			return $this->askedNativeTypes[$nodeId][1];
		}

		$type = $this->doGetNativeType($expr);
		$this->askedNativeTypes[$nodeId] = [$expr, $type];

		return $type;
	}

	private function doGetNativeType(Expr $expr): Type
	{
		$beforeScope = $this->findStoredBeforeScope($expr);

		if (
			!$this->nativeTypesPromoted
			&& count($this->truthyValueExprs) === 0
			&& count($this->falseyValueExprs) === 0
		) {
			if ($beforeScope !== null) {
				return $beforeScope->getNativeType($expr);
			}

			return $this->toWalkScope()->getNativeType($expr);
		}

		$scope = $this->preprocessScope($beforeScope ?? $this->toWalkScope());
		return $scope->getNativeType($expr);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		$beforeScope = $this->findStoredBeforeScope($node);

		$scope = $this->preprocessScope($beforeScope ?? $this->toWalkScope());

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
		// a nested walk a rule started from its NodeCallbackScope may have anchored
		// results to fiber scopes - re-entering this class's ask paths from
		// here would derive scopes without end
		$scope = $scope->toWalkScope();
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
