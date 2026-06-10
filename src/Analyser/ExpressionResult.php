<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function get_class;
use function sprintf;

final class ExpressionResult
{

	/** @var (callable(Expr, MutatingScope): Type)|null */
	private $typeCallback;

	/** @var (callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes)|null */
	private $specifyTypesCallback;

	/** @var (callable(): MutatingScope)|null */
	private $truthyScopeCallback;

	private ?MutatingScope $truthyScope = null;

	/** @var (callable(): MutatingScope)|null */
	private $falseyScopeCallback;

	private ?MutatingScope $falseyScope = null;

	private ?Type $cachedType = null;

	private ?Type $cachedNativeType = null;

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param (callable(Expr, MutatingScope): Type)|null $typeCallback
	 * @param (callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes)|null $specifyTypesCallback
	 * @param (callable(): MutatingScope)|null $truthyScopeCallback
	 * @param (callable(): MutatingScope)|null $falseyScopeCallback
	 * @param array<string, ExpressionResult> $companionResults results for companion
	 *        expressions this result's specifyTypesCallback narrows alongside its own
	 *        (the plain-chain variant of a nullsafe fetch) — applySpecifiedTypes
	 *        resolves their pre-narrowing types from here
	 */
	public function __construct(
		private MutatingScope $scope,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $throwPoints,
		private array $impurePoints,
		?callable $truthyScopeCallback = null,
		?callable $falseyScopeCallback = null,
		private ?Expr $expr = null,
		?callable $typeCallback = null,
		?callable $specifyTypesCallback = null,
		private ?ExpressionTypeResolverExtensionRegistryProvider $expressionTypeResolverExtensionRegistryProvider = null,
		private array $companionResults = [],
	)
	{
		$this->truthyScopeCallback = $truthyScopeCallback;
		$this->falseyScopeCallback = $falseyScopeCallback;
		$this->typeCallback = $typeCallback;
		$this->specifyTypesCallback = $specifyTypesCallback;
	}

	/**
	 * Attaches the processed Expr to results coming from not-yet-migrated handlers,
	 * enabling the legacy type-resolution bridge. Called by NodeScopeResolver::processExprNode().
	 *
	 * @internal
	 */
	public function setExpr(Expr $expr): void
	{
		$this->expr ??= $expr;
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

	/**
	 * `ExpressionResult::getType()` is a replacement for `MutatingScope::getType(Expr)`
	 * for use inside `ExprHandler::processExpr()` implementations.
	 */
	public function getType(): Type
	{
		if ($this->cachedType !== null) {
			return $this->cachedType;
		}

		return $this->cachedType = TypeUtils::resolveLateResolvableTypes($this->getTypeByScope($this->scope));
	}

	/**
	 * `ExpressionResult::getNativeType()` is a replacement for `MutatingScope::getNativeType(Expr)`
	 * for use inside `ExprHandler::processExpr()` implementations.
	 */
	public function getNativeType(): Type
	{
		if ($this->cachedNativeType !== null) {
			return $this->cachedNativeType;
		}

		if ($this->typeCallback === null) {
			if ($this->expr === null) {
				throw new ShouldNotHappenException('ExpressionResult native type was requested but no Expr is attached.');
			}

			// Legacy bridge for not-yet-migrated handlers. Guarded:
			// works under PHPSTAN_FNSR=0, throws the guarding exception otherwise.
			return $this->cachedNativeType = $this->scope->getNativeType($this->expr);
		}

		$promotedScope = $this->scope->doNotTreatPhpDocTypesAsCertain();
		if (!$promotedScope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		return $this->cachedNativeType = TypeUtils::resolveLateResolvableTypes($this->getTypeByScope($promotedScope));
	}

	/**
	 * Used instead of `$scope->getType(Expr)` inside the `typeCallback`. The passed scope
	 * only selects the variant (native types when `nativeTypesPromoted`); the type itself
	 * is resolved on this result's own (already-correct) scope.
	 */
	public function getTypeForScope(MutatingScope $scope): Type
	{
		if ($scope->nativeTypesPromoted) {
			return $this->getNativeType();
		}

		return $this->getType();
	}

	/**
	 * Resolves the type on the given scope, honoring narrowing applied to it
	 * *after* this expression was evaluated — rules filter their scope by a
	 * synthetic condition and then ask for types (e.g. a dynamic method call
	 * narrowed by each possible method name). Unlike `getTypeForScope()`,
	 * nothing is memoized, and resolution runs on the plain variant of the
	 * scope so the legacy bridges cannot suspend on this expression again.
	 */
	public function getTypeOnScope(MutatingScope $scope): Type
	{
		return TypeUtils::resolveLateResolvableTypes($this->getTypeByScope($scope->toMutatingScope()));
	}

	public function hasTypeCallback(): bool
	{
		return $this->typeCallback !== null && $this->expr !== null;
	}

	public function hasSpecifiedTypesCallback(): bool
	{
		return $this->specifyTypesCallback !== null && $this->expr !== null;
	}

	public function getSpecifiedTypes(MutatingScope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($this->expr === null || $this->specifyTypesCallback === null) {
			throw new ShouldNotHappenException(sprintf(
				'ExpressionResult specifyTypes was requested but the handler for %s has not been migrated.',
				$this->expr === null ? 'this expression' : get_class($this->expr),
			));
		}

		$callback = $this->specifyTypesCallback;
		return $callback($this->expr, $scope, $context);
	}

	public function getTruthyScope(): MutatingScope
	{
		if ($this->truthyScope !== null) {
			return $this->truthyScope;
		}

		// a handler-provided scope callback is authoritative: handlers pass one
		// when they can build the branch scope better than re-deriving the whole
		// condition from scratch — e.g. BooleanAnd composes the right operand's
		// truthy scope incrementally (the left narrowing is already part of it).
		// Migrated handlers must pass new-world callbacks here or none at all.
		if ($this->truthyScopeCallback !== null) {
			$callback = $this->truthyScopeCallback;
			return $this->truthyScope = $callback();
		}

		if ($this->specifyTypesCallback !== null && $this->expr !== null) {
			return $this->truthyScope = $this->scope->applySpecifiedTypes(
				$this->getSpecifiedTypes($this->scope, TypeSpecifierContext::createTruthy()),
				$this->getExprResultsForApply(),
			);
		}

		return $this->scope;
	}

	public function getFalseyScope(): MutatingScope
	{
		if ($this->falseyScope !== null) {
			return $this->falseyScope;
		}

		if ($this->falseyScopeCallback !== null) {
			$callback = $this->falseyScopeCallback;
			return $this->falseyScope = $callback();
		}

		if ($this->specifyTypesCallback !== null && $this->expr !== null) {
			return $this->falseyScope = $this->scope->applySpecifiedTypes(
				$this->getSpecifiedTypes($this->scope, TypeSpecifierContext::createFalsey()),
				$this->getExprResultsForApply(),
			);
		}

		return $this->scope;
	}

	/**
	 * Self + companions, keyed by node key — the pre-narrowing type sources
	 * for applySpecifiedTypes().
	 *
	 * @return array<string, ExpressionResult>
	 */
	public function getExprResultsForApply(): array
	{
		if ($this->expr === null) {
			throw new ShouldNotHappenException();
		}

		return $this->companionResults + [$this->scope->getNodeKey($this->expr) => $this];
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
	}

	private function getTypeByScope(MutatingScope $scope): Type
	{
		if ($this->expr === null) {
			throw new ShouldNotHappenException('ExpressionResult type was requested but no Expr is attached.');
		}

		if ($this->typeCallback === null) {
			// Legacy bridge for not-yet-migrated handlers. Guarded:
			// works under PHPSTAN_FNSR=0, throws the guarding exception otherwise.
			return $scope->getType($this->expr);
		}

		if ($this->expressionTypeResolverExtensionRegistryProvider !== null) {
			foreach ($this->expressionTypeResolverExtensionRegistryProvider->getRegistry()->getExtensions() as $extension) {
				$type = $extension->getType($this->expr, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		}

		if (
			!$this->expr instanceof Expr\Variable
			&& !$this->expr instanceof Expr\Closure
			&& !$this->expr instanceof Expr\ArrowFunction
			&& $scope->hasExpressionType($this->expr)->yes()
		) {
			$exprString = $scope->getNodeKey($this->expr);
			return $scope->expressionTypes[$exprString]->getType();
		}

		$callback = $this->typeCallback;
		return $callback($this->expr, $scope);
	}

}
