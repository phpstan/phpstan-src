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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class FiberScope extends MutatingScope
{

	/**
	 * Conditions this scope was filtered by *after* the node visit (rules call
	 * `filterByTruthyValue` with synthetic conditions — e.g. one per possible
	 * dynamic method name). Replayed onto each ExpressionResult's own scope in
	 * getType(): the answer keeps the expression's evaluation-point semantics
	 * and honors the rule's narrowing.
	 *
	 * @var Expr[]
	 */
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

	/**
	 * Suspends until the engine can deliver the ExpressionResult for the given
	 * expression — immediately when already processed, after its processExprNode
	 * finishes when not, or by processing it on demand when it is synthetic.
	 *
	 * @internal
	 */
	public function getExpressionResult(Expr $expr): ExpressionResult
	{
		/** @var ExpressionResult $result */
		$result = Fiber::suspend(
			new ExpressionResultForExprRequest($expr, $this),
		);

		return $result;
	}

	public function doNotTreatPhpDocTypesAsCertain(): Scope
	{
		$scope = parent::doNotTreatPhpDocTypesAsCertain();
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$fiberScope = $scope->toFiberScope();
		$fiberScope->truthyValueExprs = $this->truthyValueExprs;
		$fiberScope->falseyValueExprs = $this->falseyValueExprs;

		return $fiberScope;
	}

	/**
	 * The type at the expression's own evaluation point, narrowed by the
	 * conditions this scope was filtered by since the node visit.
	 *
	 * @api
	 */
	public function getType(Expr $node): Type
	{
		$result = $this->getExpressionResult($node);
		if ($this->truthyValueExprs === [] && $this->falseyValueExprs === []) {
			return $result->getTypeForScope($this);
		}

		return $result->getTypeOnScope($this->filterByValueExprs($result->getScope()));
	}

	/**
	 * Scope-walk semantics approximated by the expression result + filter replay
	 * until the dedicated getScopeType design lands — the old walk is the guarded
	 * legacy path (PHPSTAN_FNSR=0).
	 */
	public function getScopeType(Expr $expr): Type
	{
		return $this->getType($expr);
	}

	public function getScopeNativeType(Expr $expr): Type
	{
		return $this->getNativeType($expr);
	}

	/** @api */
	public function getNativeType(Expr $expr): Type
	{
		$result = $this->getExpressionResult($expr);
		if ($this->truthyValueExprs === [] && $this->falseyValueExprs === []) {
			return $result->getNativeType();
		}

		$promotedScope = $this->filterByValueExprs($result->getScope())->doNotTreatPhpDocTypesAsCertain();
		if (!$promotedScope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		return $result->getTypeOnScope($promotedScope);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		if (
			!$node instanceof Expr\Match_
			&& (
				(
					!$node instanceof Expr\FuncCall
					&& !$node instanceof Expr\MethodCall
					&& !$node instanceof Expr\NullsafeMethodCall
					&& !$node instanceof Expr\StaticCall
				) || $node->isFirstClassCallable()
			)
		) {
			return $this->getType($node);
		}

		$originalType = $this->getType($node);
		if (!TypeCombinator::containsNull($originalType)) {
			return $originalType;
		}

		// the attributed clone is a synthetic expression — the fiber suspends
		// for it and the handlers honor the attribute when resolving the
		// return type (VoidToNullTypeTransformer)
		$clonedNode = clone $node;
		$clonedNode->setAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME, true);

		return $this->getType($clonedNode);
	}

	/**
	 * Replays the rule-applied filters onto the given (plain) scope — the
	 * filtering runs through the guarded old-world machinery (PHPSTAN_FNSR=0)
	 * until narrowing by arbitrary synthetic conditions migrates.
	 */
	private function filterByValueExprs(MutatingScope $scope): MutatingScope
	{
		foreach ($this->truthyValueExprs as $expr) {
			$scope = $scope->filterByTruthyValue($expr);
		}
		foreach ($this->falseyValueExprs as $expr) {
			$scope = $scope->filterByFalseyValue($expr);
		}

		return $scope;
	}

	public function filterByTruthyValue(Expr $expr): self
	{
		/** @var self $scope */
		$scope = parent::filterByTruthyValue($expr);
		$scope->truthyValueExprs = $this->truthyValueExprs;
		$scope->truthyValueExprs[] = $expr;
		$scope->falseyValueExprs = $this->falseyValueExprs;

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
