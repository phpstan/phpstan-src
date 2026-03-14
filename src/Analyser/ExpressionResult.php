<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

#[GenerateFactory(interface: ExpressionResultFactory::class)]
final class ExpressionResult
{

	/** @var callable(Expr, MutatingScope): Type */
	private $typeCallback;

	/** @var (callable(): MutatingScope)|null */
	private $truthyScopeCallback;

	private ?MutatingScope $truthyScope = null;

	/** @var (callable(): MutatingScope)|null */
	private $falseyScopeCallback;

	private ?MutatingScope $falseyScope = null;

	private ?Type $cachedType = null;

	private ?Type $cachedNativeType = null;

	private ?Type $cachedKeepVoidType = null;

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param callable(MutatingScope): Type $typeCallback
	 * @param (callable(): MutatingScope)|null $truthyScopeCallback
	 * @param (callable(): MutatingScope)|null $falseyScopeCallback
	 */
	public function __construct(
		private ExpressionTypeResolverExtensionRegistryProvider $expressionTypeResolverExtensionRegistryProvider,
		private Expr $expr,
		private MutatingScope $scope,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $throwPoints,
		private array $impurePoints,
		callable $typeCallback,
		?callable $truthyScopeCallback = null,
		?callable $falseyScopeCallback = null,
	)
	{
		$this->typeCallback = $typeCallback;
		$this->truthyScopeCallback = $truthyScopeCallback;
		$this->falseyScopeCallback = $falseyScopeCallback;
	}

	private function withExpr(Expr $expr): self
	{
		return new self(
			$this->expressionTypeResolverExtensionRegistryProvider,
			$expr,
			$this->scope,
			$this->hasYield,
			$this->isAlwaysTerminating,
			$this->throwPoints,
			$this->impurePoints,
			$this->typeCallback,
			$this->truthyScopeCallback,
			$this->falseyScopeCallback,
		);
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

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
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
	 * `ExpressionResult::getTypeForScope(Scope)` is used
	 * instead of `$scope->getType(Expr)` inside typeCallback ExpressionResultFactory argument.
	 */
	public function getTypeForScope(MutatingScope $scope): Type
	{
		if ($scope->nativeTypesPromoted) {
			return $this->getNativeType();
		}

		return $this->getType();
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

		return $this->cachedNativeType = TypeUtils::resolveLateResolvableTypes($this->getTypeByScope($this->scope->doNotTreatPhpDocTypesAsCertain()));
	}

	public function getKeepVoidType(): Type
	{
		if ($this->cachedKeepVoidType !== null) {
			return $this->cachedKeepVoidType;
		}

		if (
			!$this->expr instanceof Match_
			&& (
				(
					!$this->expr instanceof FuncCall
					&& !$this->expr instanceof MethodCall
					&& !$this->expr instanceof Expr\NullsafeMethodCall
					&& !$this->expr instanceof Expr\StaticCall
				) || $this->expr->isFirstClassCallable()
			)
		) {
			return $this->getType();
		}

		$originalType = $this->getType();
		if (!TypeCombinator::containsNull($originalType)) {
			return $this->cachedKeepVoidType = $originalType;
		}

		$clonedExpr = clone $this->expr;
		$clonedExpr->setAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME, true);

		return $this->cachedKeepVoidType = $this->withExpr($clonedExpr)->getType();
	}

	private function getTypeByScope(MutatingScope $scope): Type
	{
		foreach ($this->expressionTypeResolverExtensionRegistryProvider->getRegistry()->getExtensions() as $extension) {
			$type = $extension->getType($this->expr, $scope);
			if ($type !== null) {
				return $type;
			}
		}

		if (
			!$this->expr instanceof Variable
			&& !$this->expr instanceof Expr\Closure
			&& !$this->expr instanceof Expr\ArrowFunction
			&& $scope->hasExpressionType($this->expr)->yes()
		) {
			$exprString = $scope->getNodeKey($this->expr);
			return $scope->expressionTypes[$exprString]->getType();
		}

		$typeCallback = $this->typeCallback;
		return $typeCallback($this->expr, $scope);
	}

}
