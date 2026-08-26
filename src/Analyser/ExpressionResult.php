<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Traverser\VoidToNullTraverser;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_keys;
use function is_string;
use function spl_object_id;

#[GenerateFactory(interface: ExpressionResultFactory::class)]
final class ExpressionResult
{

	/** @var (callable(bool): Type)|null */
	private $typeCallback;

	/** @var callable(TypeSpecifierContext, bool): SpecifiedTypes */
	private $specifyTypesCallback;

	/** @var (callable(Type, TypeSpecifierContext, bool): SpecifiedTypes)|null */
	private $createTypesCallback;

	/** @var array<int, SpecifiedTypes> */
	private array $specifiedTypes = [];

	private ?MutatingScope $truthyScope = null;

	private ?MutatingScope $falseyScope = null;

	private ?Type $cachedType = null;

	/**
	 * Whether every ExpressionTypeResolverExtension declined this expression.
	 * One full-null round settles it: a decline is (in practice) a structural
	 * decision about the expression, and re-running an expensive extension
	 * (phpstan-doctrine resolves the receiver's method reflection every time)
	 * on every read of every call-typed result is what it costs to re-ask.
	 */
	private bool $extensionsDeclined = false;

	private ?Type $cachedNativeType = null;

	private ?Type $resolvedType = null;

	private ?Type $resolvedNativeType = null;

	private ?Type $projectedType = null;

	private ?Type $projectedNativeType = null;

	/** @var list<string>|null */
	private ?array $readVariableNames = null;

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param (callable(bool): Type)|null $typeCallback
	 * @param callable(TypeSpecifierContext, bool): SpecifiedTypes $specifyTypesCallback
	 * @param (callable(Type, TypeSpecifierContext, bool): SpecifiedTypes)|null $createTypesCallback
	 * @param ExtensionsCollection<ExpressionTypeResolverExtension> $expressionTypeResolverExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: ExpressionTypeResolverExtension::class)]
		private ExtensionsCollection $expressionTypeResolverExtensions,
		private MutatingScope $scope,
		private MutatingScope $beforeScope,
		private Expr $expr,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $throwPoints,
		private array $impurePoints,
		?callable $typeCallback,
		callable $specifyTypesCallback,
		private bool $containsNullsafe = false,
		private ?IssetabilityDescriptor $issetabilityDescriptor = null,
		private ?MutatingScope $truthyScopeOverride = null,
		private ?MutatingScope $falseyScopeOverride = null,
		?callable $createTypesCallback = null,
		private ?Type $type = null,
		private ?Type $nativeType = null,
	)
	{
		// A precomputed type and a lazy typeCallback are mutually exclusive, but
		// exactly one of them must be set - a result with neither cannot answer its
		// own type. phpdoc and native types are precomputed together or not at all.
		if ($typeCallback !== null && $type !== null) {
			throw new ShouldNotHappenException('ExpressionResult cannot have both a typeCallback and a precomputed type.');
		}
		if ($typeCallback === null && $type === null) {
			throw new ShouldNotHappenException('ExpressionResult must have either a precomputed type or a typeCallback.');
		}
		if (($type === null) !== ($nativeType === null)) {
			throw new ShouldNotHappenException('ExpressionResult type and nativeType must both be set or both be null.');
		}

		$this->typeCallback = $typeCallback;
		$this->specifyTypesCallback = $specifyTypesCallback;
		$this->createTypesCallback = $createTypesCallback;
	}

	/**
	 * Turns the stored preliminary result (the type/specify callbacks published
	 * before the call handler's throw-point leg runs) into the final one in
	 * place: the resolved scope and the effects arrive, every memoized
	 * own-type/narrowing answer computed through the preliminary is carried
	 * over, and the truthy/falsey scopes derived from the preliminary scope
	 * are dropped. Equivalent to overwriting the stored result with a second
	 * object, minus the allocation and the lost memos.
	 *
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 */
	public function finalize(MutatingScope $scope, bool $hasYield, bool $isAlwaysTerminating, array $throwPoints, array $impurePoints): self
	{
		$this->scope = $scope;
		$this->hasYield = $hasYield;
		$this->isAlwaysTerminating = $isAlwaysTerminating;
		$this->throwPoints = $throwPoints;
		$this->impurePoints = $impurePoints;
		$this->truthyScope = null;
		$this->falseyScope = null;

		return $this;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getBeforeScope(): MutatingScope
	{
		return $this->beforeScope;
	}

	public function getExpr(): Expr
	{
		return $this->expr;
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
	 * The fully-resolved isset/empty/?? view of this expression on the asking
	 * scope: folds the chain descriptor, or builds a leaf resolution from the
	 * expression's own type when it is not a chain link (e.g. a method-call-rooted
	 * base like $this->getFoo()['x']). $useNativeTypes selects native vs phpdoc.
	 */
	public function getIssetabilityResolution(MutatingScope $scope, bool $useNativeTypes, bool $reprocessUntrackedLinks = false): IssetabilityResolution
	{
		if ($this->issetabilityDescriptor !== null) {
			return $this->issetabilityDescriptor->resolve($scope, $useNativeTypes, $this->expr, $reprocessUntrackedLinks);
		}

		$type = $reprocessUntrackedLinks && !$scope->hasExpressionType($this->expr)->yes()
			? ($useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain()->getNativeType($this->expr) : $scope->getType($this->expr))
			: $this->getTypeOnScope($scope, $useNativeTypes);

		return new IssetabilityResolution(
			IssetabilityLinkInfo::leaf($type, $this->expr, $this->expr instanceof Expr\NullsafePropertyFetch),
			null,
		);
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

		// && is truthy only when the right operand was evaluated (on the left-truthy
		// scope) and is itself truthy - that is exactly $rightResult->getTruthyScope(),
		// which the handler passes as $truthyScopeOverride. It already carries the left
		// operand's narrowing and the right operand's by-ref/side-effect definitions,
		// and crucially does NOT re-apply the left narrowing on top of a scope where the
		// right operand reassigned the narrowed variable (see bug-9400).
		if ($this->truthyScopeOverride !== null) {
			return $this->truthyScope = $this->truthyScopeOverride;
		}

		return $this->truthyScope = $this->scope->applySpecifiedTypes(
			$this->getSpecifiedTypes(TypeSpecifierContext::createTruthy(), $this->scope->nativeTypesPromoted),
		);
	}

	public function getFalseyScope(): MutatingScope
	{
		if ($this->falseyScope !== null) {
			return $this->falseyScope;
		}

		// || is falsey only when the right operand was evaluated (on the left-falsey
		// scope) and is itself falsey - that is exactly $rightResult->getFalseyScope().
		if ($this->falseyScopeOverride !== null) {
			return $this->falseyScope = $this->falseyScopeOverride;
		}

		return $this->falseyScope = $this->scope->applySpecifiedTypes(
			$this->getSpecifiedTypes(TypeSpecifierContext::createFalsey(), $this->scope->nativeTypesPromoted),
		);
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
	}

	public function getType(): Type
	{
		if ($this->cachedType !== null) {
			return $this->cachedType;
		}

		$extensionType = $this->consultExpressionTypeResolverExtensions($this->beforeScope);
		if ($extensionType !== null) {
			return $this->cachedType = $extensionType;
		}

		if ($this->type !== null) {
			return $this->type;
		}

		if ($this->hasOwnLazyResolution() && !$this->hasTrackedExpressionType($this->beforeScope)) {
			return $this->cachedType = $this->resolveOwnType(false);
		}

		// The guard above leaves only one way here: the expression is tracked on
		// beforeScope (typeCallback is set but a holder wins). Read the holder
		// directly instead of re-entering MutatingScope::getType().
		return $this->cachedType = $this->beforeScope->getTrackedExpressionType($this->expr);
	}

	public function getNativeType(): Type
	{
		if ($this->cachedNativeType !== null) {
			return $this->cachedNativeType;
		}

		// old-world getNativeType() promoted the scope and re-entered
		// resolveType(), extension hook included
		$extensionType = $this->extensionsDeclined ? null : $this->consultExpressionTypeResolverExtensions($this->beforeScope->doNotTreatPhpDocTypesAsCertain());
		if ($extensionType !== null) {
			return $this->cachedNativeType = $extensionType;
		}

		if ($this->nativeType !== null) {
			return $this->nativeType;
		}

		if ($this->hasOwnLazyResolution() && !$this->hasTrackedExpressionType($this->beforeScope->doNotTreatPhpDocTypesAsCertain())) {
			return $this->cachedNativeType = $this->resolveOwnType(true);
		}

		// Tracked native holder (getNativeType() promotes the scope, so its
		// expressionTypes are the native ones) - read it directly.
		return $this->cachedNativeType = $this->beforeScope->doNotTreatPhpDocTypesAsCertain()->getTrackedExpressionType($this->expr);
	}

	private function consultExpressionTypeResolverExtensions(MutatingScope $readScope): ?Type
	{
		if ($this->extensionsDeclined) {
			return null;
		}

		foreach ($this->expressionTypeResolverExtensions->getAll() as $extension) {
			$type = $extension->getType($this->expr, $readScope);
			if ($type !== null) {
				return $type;
			}
		}

		$this->extensionsDeclined = true;

		return null;
	}

	/**
	 * The result's own raw type - the eager value or the memoized typeCallback,
	 * with no tracked-holder interference. The callback is a pure function of
	 * the flavour flag, so one memo slot per flavour is exact.
	 *
	 * A void-returning call keeps `void` here; the void->null projection every
	 * value read applies happens in resolveOwnType(). getKeepVoidType() reads
	 * this raw type so a void call used as a value (assigned, passed as an
	 * argument, a void match arm) is still seen as void by the rules that
	 * flag that misuse.
	 */
	private function resolveOwnRawType(bool $nativeTypesPromoted): Type
	{
		if ($nativeTypesPromoted) {
			if ($this->nativeType !== null) {
				return $this->nativeType;
			}
			if ($this->resolvedNativeType !== null) {
				return $this->resolvedNativeType;
			}
			if ($this->typeCallback === null) {
				throw new ShouldNotHappenException();
			}

			$resolvedNativeType = TypeUtils::resolveLateResolvableTypes(($this->typeCallback)(true));
			$this->resolvedNativeType = $resolvedNativeType;
			$this->releaseTypeCallbackIfResolved();

			return $resolvedNativeType;
		}

		if ($this->type !== null) {
			return $this->type;
		}
		if ($this->resolvedType !== null) {
			return $this->resolvedType;
		}
		if ($this->typeCallback === null) {
			throw new ShouldNotHappenException();
		}

		$resolvedType = TypeUtils::resolveLateResolvableTypes(($this->typeCallback)(false));
		$this->resolvedType = $resolvedType;
		$this->releaseTypeCallbackIfResolved();

		return $resolvedType;
	}

	/**
	 * Once both flavours are memoized the callback can never be invoked again -
	 * dropping it releases its captured environment (child results, intermediate
	 * scopes) for refcount collection while the file is still being analysed.
	 */
	private function releaseTypeCallbackIfResolved(): void
	{
		if ($this->resolvedType === null || $this->resolvedNativeType === null) {
			return;
		}

		$this->typeCallback = null;
	}

	/**
	 * The result's own type as a value: the raw type with `void` projected to
	 * `null` (a void expression evaluates to null). The projection used to live
	 * in the call handlers' return-type resolution; keeping it at this single
	 * read boundary lets one raw type serve both value reads and
	 * getKeepVoidType().
	 */
	private function resolveOwnType(bool $nativeTypesPromoted): Type
	{
		if ($nativeTypesPromoted) {
			return $this->projectedNativeType ??= $this->projectVoidToNull($this->resolveOwnRawType(true));
		}

		return $this->projectedType ??= $this->projectVoidToNull($this->resolveOwnRawType(false));
	}

	private function projectVoidToNull(Type $type): Type
	{
		// void only ever originates from a call return type; the overwhelmingly
		// common non-void, non-union result skips the traverser entirely
		if ($type->isVoid()->no() && !$type instanceof UnionType) {
			return $type;
		}

		return TypeTraverser::map($type, new VoidToNullTraverser());
	}

	/**
	 * The own type with `void` kept (not projected to null) - answers
	 * Scope::getKeepVoidType() from the stored result instead of re-processing
	 * the node with a keep-void marker.
	 */
	public function getKeepVoidType(bool $nativeTypesPromoted): Type
	{
		return $this->resolveOwnRawType($nativeTypesPromoted);
	}

	/**
	 * A narrowed or ensured type tracked for the whole expression (e.g. the
	 * nullsafe handlers ensure `($x ?? null)` is not null before processing
	 * the chain) wins over recomputing the type - mirrors the tracked-holder
	 * early return in MutatingScope::resolveType(). Asking the scope is safe:
	 * its own early return answers from the holder without dispatching back.
	 */
	private function hasTrackedExpressionType(MutatingScope $scope): bool
	{
		return !$this->expr instanceof Expr\Variable
			&& !$this->expr instanceof Expr\Closure
			&& !$this->expr instanceof Expr\ArrowFunction
			&& $scope->hasExpressionType($this->expr)->yes();
	}

	/**
	 * Whether this result can answer its own type without asking the scope -
	 * either an eagerly computed value (e.g. a closure's ClosureType) or a
	 * typeCallback. The new-world resolution in MutatingScope gates on this.
	 */
	public function canResolveOwnType(): bool
	{
		return $this->type !== null || $this->hasOwnLazyResolution();
	}

	/**
	 * True while the typeCallback is alive or after it was released because both
	 * flavour memos are filled - either way the result answers its own type.
	 */
	private function hasOwnLazyResolution(): bool
	{
		return $this->typeCallback !== null || $this->resolvedType !== null;
	}

	/** Evaluates this expression's narrowing on the given scope. */
	public function getSpecifiedTypesForScope(MutatingScope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $this->getSpecifiedTypes($context, $scope->nativeTypesPromoted);
	}

	/**
	 * The expression's narrowing for the given context, computed at its own
	 * evaluation point (the flavour-mapped beforeScope) and memoized per
	 * (context, flavour). All state-dependent math lives in the symbolic
	 * SpecifiedTypes (alternative terms, holder recipes, deferred augments)
	 * and is evaluated by applySpecifiedTypes() against whichever scope the
	 * narrowing is applied to - so one memoized SpecifiedTypes serves every
	 * asking position.
	 */
	public function getSpecifiedTypes(TypeSpecifierContext $context, bool $nativeTypesPromoted = false): SpecifiedTypes
	{
		$key = (spl_object_id($context) << 1) | ($nativeTypesPromoted ? 1 : 0);

		return $this->specifiedTypes[$key] ??= ($this->specifyTypesCallback)($context, $nativeTypesPromoted);
	}

	/**
	 * How a type constraint on this expression translates into narrowing
	 * entries - the inside-out counterpart of TypeSpecifier::create(). The
	 * handler that produced this result knows the structure: an assignment
	 * fans out to the assigned variable and the assigned expression
	 * (recursing through the assigned expression's own result), a coalesce
	 * delegates to its left side when the type rules the right side in or
	 * out. Returns null when the handler wired no createTypesCallback - the
	 * caller emits a single entry for the expression itself.
	 */
	public function getCreatedTypesForScope(MutatingScope $scope, Type $type, TypeSpecifierContext $context): ?SpecifiedTypes
	{
		return $this->getCreatedTypes($type, $context, $scope->nativeTypesPromoted);
	}

	/**
	 * The narrowing entries a type constraint on this expression fans out to,
	 * computed at the expression's own evaluation point - the asking scope
	 * reduces to its flavour bit, like getSpecifiedTypes().
	 */
	public function getCreatedTypes(Type $type, TypeSpecifierContext $context, bool $nativeTypesPromoted = false): ?SpecifiedTypes
	{
		if ($this->createTypesCallback === null) {
			return null;
		}

		return ($this->createTypesCallback)($type, $context, $nativeTypesPromoted);
	}

	/**
	 * The type of this expression as the given scope sees it: a narrowed or
	 * ensured type the scope tracks for the whole expression wins over the
	 * result's own (position-time) type. For the deliberately scope-sensitive
	 * consumers - isset/empty/?? chain folding and the stored-result read in
	 * NodeScopeResolver - everything else reads getType()/getNativeType().
	 */
	public function getTypeOnScope(MutatingScope $scope, bool $useNativeTypes): Type
	{
		$readScope = $useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope;
		// old-world resolveType() consulted these on every ask, both flavours -
		// a consumer reading a call's type here (an assign filling the target's
		// holder) must see the override or it never enters the scope state
		$extensionType = $this->consultExpressionTypeResolverExtensions($readScope);
		if ($extensionType !== null) {
			return $extensionType;
		}

		if ($this->type === null && $this->isScopeAuthoritative($readScope)) {
			// the state read is a value read: resolve late-resolvable types and
			// project void to null exactly like resolveOwnType() does
			return $this->projectVoidToNull(TypeUtils::resolveLateResolvableTypes($readScope->getStateType($this->expr)));
		}

		return $this->resolveOwnType($useNativeTypes);
	}

	/**
	 * Whether getTypeOnScope() gives the correct answer at the given (foreign)
	 * position without re-pricing the expression there: the answer is
	 * position-independent (eager type), the scope owns it (tracked variable or
	 * expression - including narrowing and invalidation of this very
	 * expression), or nothing the expression reads changed since the walk.
	 * When this is false, the caller must reprocess the expression on the
	 * asking scope.
	 */
	public function answersOnScope(MutatingScope $scope, bool $useNativeTypes): bool
	{
		if ($this->type !== null) {
			return true;
		}

		$readScope = $useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope;

		return $this->isScopeAuthoritative($readScope) || $this->askScopeVariableStateMatches($scope, $useNativeTypes);
	}

	/**
	 * Whether the given scope, not this result, owns the answer to "what is
	 * this expression here": narrowable expressions the scope knows (variables
	 * including $this and parameters, tracked fetches) and any expression the
	 * scope tracks a holder for (ensured non-nullability, remembered values).
	 * Evaluating this result's expression at a foreign position must read
	 * those from that position's state - the memoized walk-position type
	 * predates whatever narrowing or invalidation the scope carries.
	 */
	private function isScopeAuthoritative(MutatingScope $scope): bool
	{
		if ($this->expr instanceof Expr\Variable) {
			return is_string($this->expr->name) && !$scope->hasVariableType($this->expr->name)->no();
		}

		return !$this->expr instanceof Expr\Closure
			&& !$this->expr instanceof Expr\ArrowFunction
			&& $scope->hasExpressionType($this->expr)->yes();
	}

	/**
	 * Whether the asking scope agrees with this result's evaluation position on
	 * every variable the expression reads. A counterfactual ask - an extension
	 * re-binding a variable (e.g. array_filter evaluating its callback body per
	 * constant element) and pricing a real node - must not be answered from the
	 * memoized walk-position type; the caller re-prices the node on the asking
	 * scope instead.
	 */
	/**
	 * $ruleFacingAsk: a NodeCallbackScope ask tolerates walk-side divergence - a
	 * variable the asking scope has no opinion on (born inside the asked node,
	 * past the ask position) and a variable NARROWER at the evaluation
	 * position (the coalesce right side priced on the left's falsey branch)
	 * both leave the walk answer standing; only an asker-side refinement (a
	 * callback pinning a call-site literal onto a parameter) re-prices.
	 * Engine-side consumers keep the strict direction: any state divergence -
	 * including a variable removed since the walk - forces re-pricing.
	 */
	public function askScopeVariableStateMatches(MutatingScope $scope, bool $useNativeTypes, bool $ruleFacingAsk = false): bool
	{
		// same unpromoted position implies same promoted position - skip the
		// flavour derivation for the common same-position ask
		if ($scope === $this->beforeScope) {
			return true;
		}
		// a closure's stored result IS its (by-ref converged) walk; re-walking
		// it at a foreign position would re-run the whole convergence loop. Its
		// body variables are not reads of the asking position, and the
		// position-sensitive TYPE is computed by getClosureType at ask sites.
		if ($this->expr instanceof Expr\Closure || $this->expr instanceof Expr\ArrowFunction) {
			return true;
		}
		$names = $this->getReadVariableNames();
		if ($names === []) {
			return true;
		}

		$readScope = $useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope;
		$positionScope = $useNativeTypes ? $this->beforeScope->doNotTreatPhpDocTypesAsCertain() : $this->beforeScope;
		if ($readScope === $positionScope) {
			return true;
		}

		foreach ($names as $name) {
			$askKnows = $readScope->hasVariableType($name);
			$positionKnows = $positionScope->hasVariableType($name);
			if ($ruleFacingAsk) {
				if ($askKnows->no()) {
					continue;
				}
				if ($positionKnows->no()) {
					return false;
				}
				$askType = $readScope->getVariableType($name);
				$positionType = $positionScope->getVariableType($name);
				// identity and equality short-circuit the O(keys^2) constant-array
				// isSuperTypeOf() - unchanged variables are the common ask case
				if ($askType === $positionType || $askType->equals($positionType)) {
					continue;
				}
				if ($askType->isSuperTypeOf($positionType)->yes()) {
					continue;
				}

				return false;
			}
			if ($askKnows->no() && $positionKnows->no()) {
				continue;
			}
			if (!$askKnows->equals($positionKnows)) {
				return false;
			}
			$askType = $readScope->getVariableType($name);
			$positionType = $positionScope->getVariableType($name);
			if ($askType !== $positionType && !$askType->equals($positionType)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A copy of this result answering at a foreign ask position: the scopes are
	 * re-anchored to the asking scope so an on-demand walk consuming this
	 * answer threads ITS position onward, not the original walk's. The
	 * position-dependent branch-scope memos and overrides are dropped - they
	 * belong to the original position and derive from the ask scope on demand.
	 */
	public function atAskPosition(MutatingScope $scope): self
	{
		$clone = clone $this;
		$clone->scope = $scope;
		$clone->beforeScope = $scope;
		$clone->truthyScope = null;
		$clone->falseyScope = null;
		$clone->truthyScopeOverride = null;
		$clone->falseyScopeOverride = null;
		$clone->cachedType = null;
		$clone->cachedNativeType = null;
		// a scope-authoritative expression's type is pinned eagerly from the ask
		// position's state - the original callbacks capture the original
		// position's scopes and would answer stale types (e.g. a variable
		// receiver consumed on an ensured-non-null scope)
		if ($this->type === null && $this->isScopeAuthoritative($scope)) {
			$clone->type = $scope->getStateType($this->expr);
			$clone->nativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getStateType($this->expr);
			$clone->typeCallback = null;
			$clone->resolvedType = null;
			$clone->resolvedNativeType = null;
			$clone->projectedType = null;
			$clone->projectedNativeType = null;
		}

		return $clone;
	}

	/**
	 * @return list<string>
	 */
	private function getReadVariableNames(): array
	{
		if ($this->readVariableNames !== null) {
			return $this->readVariableNames;
		}

		$visitor = new class extends NodeVisitorAbstract {

			/** @var array<string, true> */
			public array $names = [];

			#[Override]
			public function enterNode(Node $node): ?int
			{
				if ($node instanceof Expr\Variable && is_string($node->name) && $node->name !== 'this') {
					$this->names[$node->name] = true;
				}
				// a closure body's variables live in its own scope - only the
				// use() clause reads the enclosing position. Arrow functions
				// capture implicitly and are traversed.
				if ($node instanceof Expr\Closure) {
					foreach ($node->uses as $use) {
						if (!is_string($use->var->name)) {
							continue;
						}
						$this->names[$use->var->name] = true;
					}

					return NodeVisitor::DONT_TRAVERSE_CHILDREN;
				}

				return null;
			}

		};
		$traverser = new NodeTraverser();
		$traverser->addVisitor($visitor);
		$traverser->traverse([$this->expr]);

		return $this->readVariableNames = array_keys($visitor->names);
	}

}
