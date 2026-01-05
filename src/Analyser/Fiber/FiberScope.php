<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;
use function count;
use function defined;

final class FiberScope extends MutatingScope
{

	private const EXPR_TYPE_ATTRIBUTE_NAME = 'fnsrType';

	private const EXPR_NATIVE_TYPE_ATTRIBUTE_NAME = 'fnsrNativeType';

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
		$shouldCache = defined('__PHPSTAN_RUNNING__') && !$this->isInTrait() && count($this->truthyValueExprs) === 0 && count($this->falseyValueExprs) === 0 && !$this->nativeTypesPromoted;
		if ($shouldCache) {
			$cachedType = $node->getAttribute(self::EXPR_TYPE_ATTRIBUTE_NAME);
			if ($cachedType !== null) {
				return $cachedType;
			}
		}

		/** @var Scope $beforeScope */
		$beforeScope = Fiber::suspend(
			new BeforeScopeForExprRequest($node, $this),
		);

		$scope = $this->preprocessScope($beforeScope->toMutatingScope());
		$type = $scope->getType($node);

		if ($shouldCache) {
			$node->setAttribute(self::EXPR_TYPE_ATTRIBUTE_NAME, $type);
		}

		return $type;
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
		$shouldCache = defined('__PHPSTAN_RUNNING__') && !$this->isInTrait() && count($this->truthyValueExprs) === 0 && count($this->falseyValueExprs) === 0 && !$this->nativeTypesPromoted;
		if ($shouldCache) {
			$cachedType = $expr->getAttribute(self::EXPR_NATIVE_TYPE_ATTRIBUTE_NAME);
			if ($cachedType !== null) {
				return $cachedType;
			}
		}

		/** @var Scope $beforeScope */
		$beforeScope = Fiber::suspend(
			new BeforeScopeForExprRequest($expr, $this),
		);

		$scope = $this->preprocessScope($beforeScope->toMutatingScope());
		$type = $scope->getNativeType($expr);

		if ($shouldCache) {
			$expr->setAttribute(self::EXPR_NATIVE_TYPE_ATTRIBUTE_NAME, $type);
		}

		return $type;
	}

	public function getKeepVoidType(Expr $node): Type
	{
		/** @var Scope $beforeScope */
		$beforeScope = Fiber::suspend(
			new BeforeScopeForExprRequest($node, $this),
		);

		$scope = $this->preprocessScope($beforeScope->toMutatingScope());

		return $scope->getKeepVoidType($node);
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
		// no need to track this in rules, the type will be correct anyway
		return $this;
	}

	public function popInFunctionCall(): self
	{
		// no need to track this in rules, the type will be correct anyway
		return $this;
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
