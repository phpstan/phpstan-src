<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function array_key_exists;

/**
 * New-world adapter for code that receives a Scope and calls getType() on it
 * mid-analysis: dynamic return type extensions, type-specifying extensions, and
 * not-yet-rewritten resolveType()/specifyTypes() bodies invoked from inside
 * ExpressionResult callbacks.
 *
 * Unlike FiberScope (used for rule node-callbacks, which run before the
 * expression is processed and therefore suspend), this adapter never suspends:
 * by the time it is used, all child expressions are already processed. It
 * resolves types in tiers:
 *
 *   1. ExpressionTypeResolverExtensions (same priority as the old resolveType),
 *   2. scope-tracked expression type (unguarded internal read),
 *   3. known child ExpressionResults supplied by the handler,
 *   4. inline re-processing of the expression on the underlying plain scope —
 *      this is what makes synthetic expressions (built by extensions and
 *      old-world helper code) work without suspension.
 */
final class ResultAwareScope extends MutatingScope
{

	/** @var array<string, ExpressionResult> */
	private array $exprResults = [];

	private ?MutatingScope $plainScope = null;

	private ?NodeScopeResolver $nodeScopeResolver = null;

	private ?Stmt $stmt = null;

	private ?ExpressionResultStorage $resultStorage = null;

	private ?self $promotedScope = null;

	/**
	 * @param array<string, ExpressionResult> $exprResults
	 *
	 * @internal
	 */
	public function initializeResultAware(
		MutatingScope $plainScope,
		array $exprResults,
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		ExpressionResultStorage $resultStorage,
	): void
	{
		$this->plainScope = $plainScope;
		$this->exprResults = $exprResults;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->stmt = $stmt;
		$this->resultStorage = $resultStorage;
	}

	public function toResultAwareScope(array $exprResults, NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ExpressionResultStorage $storage): self
	{
		if ($this->plainScope === null) {
			throw new ShouldNotHappenException();
		}

		// don't wrap an adapter in an adapter — merge the known results instead
		return $this->plainScope->toResultAwareScope($exprResults + $this->exprResults, $nodeScopeResolver, $stmt, $storage);
	}

	/** @api */
	public function getType(Expr $node): Type
	{
		return TypeUtils::resolveLateResolvableTypes($this->resolveTypeViaResults($node));
	}

	/** @api */
	public function getNativeType(Expr $expr): Type
	{
		$scope = $this->doNotTreatPhpDocTypesAsCertain();
		if (!$scope instanceof self) {
			throw new ShouldNotHappenException();
		}

		return $scope->getType($expr);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		// keepVoid is a one-off solved separately; fall back to the regular type for now
		return $this->getType($node);
	}

	public function doNotTreatPhpDocTypesAsCertain(): Scope
	{
		if ($this->nativeTypesPromoted) {
			return $this;
		}

		if ($this->promotedScope !== null) {
			return $this->promotedScope;
		}

		if ($this->plainScope === null || $this->nodeScopeResolver === null || $this->stmt === null || $this->resultStorage === null) {
			throw new ShouldNotHappenException();
		}

		$promotedPlainScope = $this->plainScope->doNotTreatPhpDocTypesAsCertain();
		if (!$promotedPlainScope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		return $this->promotedScope = $promotedPlainScope->toResultAwareScope(
			$this->exprResults,
			$this->nodeScopeResolver,
			$this->stmt,
			$this->resultStorage,
		);
	}

	/**
	 * Resolves SpecifiedTypes for the given expr through ExpressionResults —
	 * called from the head of TypeSpecifier::specifyTypesInCondition() so that
	 * old-world narrowing code recursing with this scope stays in the new world.
	 *
	 * @internal
	 */
	public function specifyTypesViaResults(Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		$key = $this->getNodeKey($expr);
		if (array_key_exists($key, $this->exprResults)) {
			return $this->exprResults[$key]->getSpecifiedTypes($this, $context);
		}

		return $this->processSynthetic($expr)->getSpecifiedTypes($this, $context);
	}

	/**
	 * Scope-deriving methods create new instances through the scope factory —
	 * carry the adapter context over, mirroring FiberScope.
	 *
	 * @param FunctionReflection|MethodReflection|null $reflection
	 */
	public function pushInFunctionCall($reflection, ?ParameterReflection $parameter, bool $rememberTypes): self
	{
		$scope = parent::pushInFunctionCall($reflection, $parameter, $rememberTypes);
		if (!$scope instanceof self) {
			throw new ShouldNotHappenException();
		}

		$scope->copyResultAwareContextFrom($this);

		return $scope;
	}

	public function popInFunctionCall(): self
	{
		$scope = parent::popInFunctionCall();
		if (!$scope instanceof self) {
			throw new ShouldNotHappenException();
		}

		$scope->copyResultAwareContextFrom($this);

		return $scope;
	}

	private function copyResultAwareContextFrom(self $other): void
	{
		$this->plainScope = $other->plainScope;
		$this->exprResults = $other->exprResults;
		$this->nodeScopeResolver = $other->nodeScopeResolver;
		$this->stmt = $other->stmt;
		$this->resultStorage = $other->resultStorage;
	}

	private function resolveTypeViaResults(Expr $node): Type
	{
		foreach ($this->expressionTypeResolverExtensionRegistry->getExtensions() as $extension) {
			$type = $extension->getType($node, $this);
			if ($type !== null) {
				return $type;
			}
		}

		if (
			!$node instanceof Expr\Variable
			&& !$node instanceof Expr\Closure
			&& !$node instanceof Expr\ArrowFunction
			&& $this->hasExpressionType($node)->yes()
		) {
			return $this->expressionTypes[$this->getNodeKey($node)]->getType();
		}

		$key = $this->getNodeKey($node);
		if (array_key_exists($key, $this->exprResults)) {
			return $this->exprResults[$key]->getTypeForScope($this);
		}

		if ($this->plainScope === null) {
			// derived through a scope-mutation path that did not carry the adapter
			// context — degrade to the guarded legacy bridge (PHPSTAN_FNSR=0)
			return parent::getType($node);
		}

		return $this->processSynthetic($node)->getTypeForScope($this);
	}

	private function processSynthetic(Expr $expr): ExpressionResult
	{
		if ($this->plainScope === null || $this->nodeScopeResolver === null || $this->stmt === null || $this->resultStorage === null) {
			throw new ShouldNotHappenException('ResultAwareScope is missing its adapter context.');
		}

		return $this->nodeScopeResolver->processExprNode(
			$this->stmt,
			$expr,
			$this->plainScope,
			$this->resultStorage->duplicate(),
			new NoopNodeCallback(),
			ExpressionContext::createDeep(),
		);
	}

}
