<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ExprHandler\ArrowFunctionHandler;
use PHPStan\Analyser\ExprHandler\AssignHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureParameterResolver;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper;
use PHPStan\Analyser\Generics\TemplateArgumentConstraints;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Analyser\Generics\TemplateArgumentObserver;
use PHPStan\Analyser\Generics\TemplateArgumentStats;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\File\FileHelper;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\FunctionCallExpressionNode;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\MethodCallExpressionNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\ReturnAfterFinallyNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Node\StaticMethodCallExpressionNode;
use PHPStan\Parser\ArrowFunctionArgVisitor;
use PHPStan\Parser\ClosureArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_pop;
use function count;
use function get_class;
use function getenv;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function spl_object_id;
use function sprintf;

#[AutowiredService]
class NodeScopeResolver
{

	public const LOOP_SCOPE_ITERATIONS = 3;
	public const GENERALIZE_AFTER_ITERATION = 1;

	/** @var array<string, true> filePath(string) => bool(true) */
	private array $analysedFiles = [];

	/**
	 * When processing a synthetic node on demand, real AST
	 * nodes contained in it were already processed and must not be processed again.
	 */
	protected bool $returnStoredExpressionResults = false;

	/**
	 * Consume-stored mode: a walk that deliberately re-enters an
	 * already-walked subtree (the nullsafe plain twin re-walking its
	 * receiver) consumes stored results unconditionally instead of
	 * re-processing - node callbacks fired during the original walk.
	 */
	private bool $consumeStoredExpressionResults = false;

	private ?NonNullabilityHelper $nonNullabilityHelper = null;

	/**
	 * Engine-feeding gatherer frames (return statements, execution ends,
	 * impure points, ...), innermost last. callNodeCallback() feeds every
	 * frame the raw walk scope at the emission position - gatherers are
	 * engine code and never ask about types, and their arrays are read as
	 * soon as the enclosing body walk returns.
	 *
	 * @var list<callable(Node, Scope): void>
	 */
	private array $nodeGatherers = [];

	/** Whether the PHPSTAN_GUARD_NW diagnostic is enabled (cached from the env). */
	public static bool $guardNewWorld = false;

	/**
	 * spl_object_id => true of every Expr in the file's parsed AST. Populated
	 * only when the PHPSTAN_GUARD_NW diagnostic is enabled, so the guards can
	 * tell a real AST node from a node a rule built during analysis (which
	 * legitimately resolves on demand). Static so MutatingScope can read it.
	 *
	 * @var array<int, true>
	 */
	public static array $guardRealExprIds = [];

	/**
	 * spl_object_id => true of every Expr already processed by processExprNode
	 * in the current file. Used by the MutatingScope::getType guard to detect a
	 * real AST node whose type is asked before it was processed.
	 *
	 * @var array<int, true>
	 */
	public static array $guardProcessedExprIds = [];

	/**
	 * @param ExtensionsCollection<ReadWritePropertiesExtension> $readWritePropertiesExtensions
	 * @param ExtensionsCollection<PerFileAnalysisResettable> $perFileAnalysisResettables
	 */
	public function __construct(
		private readonly Container $container,
		private readonly TemplateArgumentObserver $templateArgumentObserver,
		private readonly ReflectionProvider $reflectionProvider,
		private readonly FileHelper $fileHelper,
		#[AutowiredExtensions(of: ReadWritePropertiesExtension::class)]
		private readonly ExtensionsCollection $readWritePropertiesExtensions,
		#[AutowiredExtensions(of: PerFileAnalysisResettable::class)]
		private readonly ExtensionsCollection $perFileAnalysisResettables,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithLoopInitialAssignments,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithAlwaysIterableForeach,
		#[AutowiredParameter]
		private readonly bool $treatPhpDocTypesAsCertain,
		private readonly ExpressionResultFactory $expressionResultFactory,
		private readonly StatementsHandler $statementsHandler,
		private readonly ArgumentsHandler $argumentsHandler,
	)
	{
		self::$guardNewWorld = getenv('PHPSTAN_GUARD_NW') === '1';
		TemplateArgumentStats::enableFromEnvironment();
	}

	/**
	 * The lookups (isAnalysedFile()) are keyed by normalized paths, so the
	 * given paths are normalized here - a caller-provided unnormalized path
	 * (mixed directory separators on Windows) must not silently skip the
	 * in-class-context analysis of a trait.
	 *
	 * @api
	 * @param string[] $files
	 */
	public function setAnalysedFiles(array $files): void
	{
		$analysedFiles = [];
		foreach ($files as $file) {
			$analysedFiles[$this->fileHelper->normalizePath($file)] = true;
		}
		$this->analysedFiles = $analysedFiles;
	}

	/**
	 * Releases the previous file's node-keyed captures: the parser cache
	 * retains ASTs, so node-keyed cache entries never die on
	 * their own and would hold that file's whole result graph alive.
	 *
	 * Called at the per-file boundary (FileAnalyser), NOT in processNodes():
	 * extensions start nested processNodes() walks mid-file (phpstan-doctrine
	 * parsing a query-builder method, rule tooling re-analysing a callee) and
	 * wiping the per-file caches there forces the outer file to rebuild them -
	 * closure types re-converge, narrowing memos recompute.
	 */
	private function getNonNullabilityHelper(): NonNullabilityHelper
	{
		return $this->nonNullabilityHelper ??= $this->container->getByType(NonNullabilityHelper::class);
	}

	public function resetPerFileAnalysisState(): void
	{
		foreach ($this->perFileAnalysisResettables->getAll() as $resettableService) {
			$resettableService->resetFileAnalysisState();
		}
	}

	/**
	 * @api
	 * @param Node[] $nodes
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processNodes(
		array $nodes,
		MutatingScope $scope,
		callable $nodeCallback,
	): void
	{
		$scope = $scope->toWalkScope();
		if (self::$guardNewWorld) {
			self::$guardRealExprIds = [];
			self::$guardProcessedExprIds = [];
			foreach ((new NodeFinder())->findInstanceOf($nodes, Expr::class) as $realExpr) {
				self::$guardRealExprIds[spl_object_id($realExpr)] = true;
			}
		}

		$expressionResultStorage = new ExpressionResultStorage();
		$scope->pushExpressionResultStorage($expressionResultStorage);
		// a fresh walk an extension starts mid-analysis must not feed the
		// interrupted walk's gatherer frames (see processStmtNodes())
		$gatherers = $this->suspendNodeGatherers();
		try {
			$this->statementsHandler->processNodesWithStorage($this, $nodes, $scope, $expressionResultStorage, $nodeCallback);
		} finally {
			$this->restoreNodeGatherers($gatherers);
			$scope->popExpressionResultStorage();
		}
	}

	/** The stored result an outside asker may consume. */
	public function findSettledExpressionResult(ExpressionResultStorage $storage, Expr $expr): ?ExpressionResult
	{
		return $storage->findExpressionResult($expr);
	}

	/** An effect-free result carrying eagerly known types, positioned at the given scope. */
	protected function createEagerExpressionResult(MutatingScope $scope, Expr $expr, Type $type, Type $nativeType): ExpressionResult
	{
		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: null,
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			type: $type,
			nativeType: $nativeType,
		);
	}

	public function storeExpressionResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $expressionResult): void
	{
		if (self::$guardNewWorld) {
			self::$guardProcessedExprIds[spl_object_id($expr)] = true;
		}
		// handlers are answered from stored results in both worlds
		$storage->storeExpressionResult($expr, $expressionResult);
	}

	/**
	 * Narrows a scope by a (often synthetic) control-flow condition the new-world
	 * way: resolve its narrowing through the scope's on-demand dispatcher and apply
	 * it via applySpecifiedTypes, instead of the old-world filterBy*Value().
	 */
	public function narrowScopeWithCondition(MutatingScope $scope, Expr $expr, TypeSpecifierContext $context): MutatingScope
	{
		$specifiedTypes = $scope->specifyTypesOfNewWorldHandlerNode($expr, $context);

		return $scope->applySpecifiedTypes($specifiedTypes);
	}

	/**
	 * @api
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processStmtNodes(
		Node $parentNode,
		array $stmts,
		MutatingScope $scope,
		callable $nodeCallback,
		StatementContext $context,
	): StatementResult
	{
		// a rule may pass the scope it was handed - the rule-facing NodeCallbackScope -
		// as the walk's initial scope; the walk must anchor its results to the
		// state-identical MutatingScope or their consumption re-enters the
		// rule-facing ask paths
		$scope = $scope->toWalkScope();
		$storage = new ExpressionResultStorage();
		$scope->pushExpressionResultStorage($storage);
		// a fresh walk an extension starts mid-analysis must not feed the
		// interrupted walk's gatherer frames - they describe the body walk
		// that was interrupted, not the nested one
		$gatherers = $this->suspendNodeGatherers();
		try {
			return $this->processStmtNodesInternal(
				$parentNode,
				$stmts,
				$scope,
				$storage,
				$nodeCallback,
				$context,
			)->toPublic();
		} finally {
			$this->restoreNodeGatherers($gatherers);
			$scope->popExpressionResultStorage();
		}
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processStmtNodesInternal(
		Node $parentNode,
		array $stmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		// make the storage this walk writes into scope-visible: loop-convergence
		// passes (including the closure by-ref convergence, which calls this
		// method directly) thread a throwaway duplicate that would otherwise
		// never reach the storage stack, so every in-pass ask
		// (applySpecifiedTypes pricing, rules via Scope::getType) would miss the
		// pass's own results and re-process real nodes on demand
		$pushStorage = $scope->getCurrentExpressionResultStorage() !== $storage;
		if ($pushStorage) {
			$scope->pushExpressionResultStorage($storage);
		}
		try {
			return $this->statementsHandler->doProcessStmtNodes($this, $parentNode, $stmts, $scope, $storage, $nodeCallback, $context);
		} finally {
			if ($pushStorage) {
				$scope->popExpressionResultStorage();
			}
		}
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processStmtNode(
		Node\Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$overridingThrowPoints = null;
		if (
			!$stmt instanceof Static_
			&& !$stmt instanceof Node\Stmt\Global_
			&& !$stmt instanceof Node\Stmt\Property
			&& !$stmt instanceof Node\Stmt\ClassConst
			&& !$stmt instanceof Node\Stmt\Const_
			&& !$stmt instanceof Node\Stmt\ClassLike
			&& !$stmt instanceof Node\Stmt\Function_
			&& !$stmt instanceof Node\Stmt\ClassMethod
		) {
			if (!$stmt instanceof Foreach_) {
				$scope = $this->statementsHandler->processStmtVarAnnotation($this, $scope, $storage, $stmt, null, $nodeCallback);
			}
			$overridingThrowPoints = $this->statementsHandler->getOverridingThrowPoints($stmt, $scope);
		}

		if ($stmt instanceof Node\Stmt\ClassMethod) {
			// a trait method the using class overrides is not analysed here at all -
			// decided before the node callback is emitted
			if (!$scope->isInClass()) {
				throw new ShouldNotHappenException();
			}
			if (
				$scope->isInTrait()
				&& $scope->getClassReflection()->hasNativeMethod($stmt->name->toString())
			) {
				$methodReflection = $scope->getClassReflection()->getNativeMethod($stmt->name->toString());
				if ($methodReflection instanceof NativeMethodReflection) {
					return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
				}
				if ($methodReflection instanceof PhpMethodReflection) {
					$declaringTrait = $methodReflection->getDeclaringTrait();
					if ($declaringTrait === null || $declaringTrait->getName() !== $scope->getTraitReflection()->getName()) {
						return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
					}
				}
			}
		}

		// Statements whose work is processing their expressions emit their node
		// callback AFTER that processing, inside their branches below, with the
		// entry scope - a synchronously invoked rule (the plain resolver,
		// PHP < 8.1) then finds the expressions' results in the storage instead
		// of re-walking them on demand, mirroring processExprNodeInternal().
		$deferredStmtCallback = $stmt instanceof Return_ || $stmt instanceof Node\Stmt\Expression || $stmt instanceof Echo_
			|| $stmt instanceof If_ || $stmt instanceof Switch_ || $stmt instanceof Foreach_
			|| $stmt instanceof Node\Stmt\Unset_ || $stmt instanceof Node\Stmt\ClassConst
			|| $stmt instanceof Node\Stmt\Const_ || $stmt instanceof Node\Stmt\While_
			|| $stmt instanceof Node\Stmt\Do_;
		if (!$deferredStmtCallback) {
			$this->callNodeCallback($nodeCallback, $stmt, $scope, $storage);
		}

		$stmtHandler = StmtHandlerRegistry::resolve($stmt, $this->container);
		if ($stmtHandler !== null) {
			$stmtResult = $stmtHandler->processStmt($this, $stmt, $scope, $storage, $nodeCallback, $context);
			if ($overridingThrowPoints !== null) {
				// the overriding throw points use the scope before the statement,
				// so the variable flow throws before the statement does its work
				$overridingThrowFlows = [];
				foreach ($overridingThrowPoints as $overridingThrowPoint) {
					$overridingThrowFlows[] = VariableFlow::throwing($overridingThrowPoint->getType(), true, $overridingThrowPoint->canContainAnyThrowable());
				}

				return new InternalStatementResult(
					$stmtResult->getScope(),
					hasYield: $stmtResult->hasYield(),
					isAlwaysTerminating: $stmtResult->isAlwaysTerminating(),
					exitPoints: $stmtResult->getExitPoints(),
					throwPoints: $overridingThrowPoints,
					impurePoints: $stmtResult->getImpurePoints(),
					endStatements: $stmtResult->getEndStatements(),
					variableFlow: VariableFlow::sequence(...$overridingThrowFlows, ...[$stmtResult->getVariableFlow()]),
				);
			}

			return $stmtResult;
		}

		// statements with no analysis of their own (e.g. HaltCompiler)
		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: $overridingThrowPoints ?? [], impurePoints: []);
	}

	public function isAnalysedFile(string $fileName): bool
	{
		return isset($this->analysedFiles[$fileName]);
	}

	public function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return $this->polluteScopeWithLoopInitialAssignments;
	}

	public function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return $this->polluteScopeWithAlwaysIterableForeach;
	}

	public function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	/** @return ExtensionsCollection<ReadWritePropertiesExtension> */
	public function getReadWritePropertiesExtensions(): ExtensionsCollection
	{
		return $this->readWritePropertiesExtensions;
	}

	/** Whether an on-demand walk answers already processed real nodes from their stored results (see processExprOnDemand()). */
	public function isReturningStoredExpressionResults(): bool
	{
		return $this->returnStoredExpressionResults;
	}

	/** Whether the walk consumes stored results unconditionally (see processExprNodeConsumingStored()). */
	public function isConsumingStoredExpressionResults(): bool
	{
		return $this->consumeStoredExpressionResults;
	}

	public function lookForSetAllowedUndefinedExpressions(MutatingScope $scope, Expr $expr): MutatingScope
	{
		return $this->lookForExpressionCallback($scope, $expr, static fn (MutatingScope $scope, Expr $expr): MutatingScope => $scope->setAllowedUndefinedExpression($expr));
	}

	public function lookForUnsetAllowedUndefinedExpressions(MutatingScope $scope, Expr $expr): MutatingScope
	{
		return $this->lookForExpressionCallback($scope, $expr, static fn (MutatingScope $scope, Expr $expr): MutatingScope => $scope->unsetAllowedUndefinedExpression($expr));
	}

	/**
	 * @param Closure(MutatingScope $scope, Expr $expr): MutatingScope $callback
	 */
	private function lookForExpressionCallback(MutatingScope $scope, Expr $expr, Closure $callback): MutatingScope
	{
		if (!$expr instanceof ArrayDimFetch || $expr->dim !== null) {
			$scope = $callback($scope, $expr);
		}

		if ($expr instanceof ArrayDimFetch) {
			$scope = $this->lookForExpressionCallback($scope, $expr->var, $callback);
		} elseif ($expr instanceof PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch || $expr instanceof Expr\NullsafeMethodCall) {
			$scope = $this->lookForExpressionCallback($scope, $expr->var, $callback);
		} elseif ($expr instanceof StaticPropertyFetch && $expr->class instanceof Expr) {
			$scope = $this->lookForExpressionCallback($scope, $expr->class, $callback);
		} elseif ($expr instanceof List_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				$scope = $this->lookForExpressionCallback($scope, $item->value, $callback);
			}
		}

		return $scope;
	}

	/**
	 * Processes an expression outside the normal AST traversal - e.g. a synthetic
	 * node a rule or extension asks about. Real AST nodes contained in it return
	 * their already-stored results instead of being processed again. New results
	 * are stored into the given storage - pass a duplicate to keep them isolated.
	 */
	/**
	 * Processes an expression whose already-walked subtrees must be CONSUMED
	 * from their stored results instead of re-walked: the nullsafe handlers
	 * process the receiver once (real callbacks) and then walk the plain twin,
	 * whose receiver subtree answers from storage, re-anchored to the twin's
	 * (ensured) scope.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processExprNodeConsumingStored(Node\Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$previous = $this->consumeStoredExpressionResults;
		$this->consumeStoredExpressionResults = true;
		try {
			return $this->processExprNode($stmt, $expr, $scope, $storage, $nodeCallback, $context);
		} finally {
			$this->consumeStoredExpressionResults = $previous;
		}
	}

	public function processExprOnDemand(Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage): ExpressionResult
	{
		// A node no handler supports - a virtual node (BooleanOrNode, ...) a
		// rule asked the type of - degrades to mixed, mirroring
		// MutatingScope::resolveType()'s fallback. The main walk's unhandled
		// throw stays: real source nodes must have a handler.
		if (
			ExprHandlerRegistry::resolve($expr, $this->container) === null
			&& !($expr instanceof Expr\CallLike && $expr->isFirstClassCallable())
		) {
			return $this->createEagerExpressionResult($scope, $expr, new MixedType(), new MixedType());
		}

		// save/restore, never reset: on-demand walks nest (a typeCallback
		// evaluated mid-walk prices another synthetic node) and a hard reset
		// would turn stored-result consumption off for the rest of the outer
		// walk - re-processing every remaining subtree and bypassing the
		// closure-argument consume guards in ArgumentsHandler::processArgs()
		$previous = $this->returnStoredExpressionResults;
		$this->returnStoredExpressionResults = true;
		$scope->pushExpressionResultStorage($storage);
		try {
			return $this->processExprNode(
				new Node\Stmt\Expression($expr),
				$expr,
				$scope,
				$storage,
				new NoopNodeCallback(),
				ExpressionContext::createTopLevel(resolveTemplateArguments: false),
			);
		} finally {
			$scope->popExpressionResultStorage();
			$this->returnStoredExpressionResults = $previous;
		}
	}

	/**
	 * Processes an expression for an independent analysis pass - a reflection-
	 * level lazy computation that builds its own scope from scratch (e.g.
	 * private property type inference from constructor assignments). Such a
	 * pass legitimately prices real AST nodes outside the file's main walk,
	 * on a fresh storage of its own.
	 */
	public function processIndependentPassExpr(Expr $expr, MutatingScope $scope): ExpressionResult
	{
		return $this->processExprOnDemand($expr, $scope, new ExpressionResultStorage());
	}

	/**
	 * The stored ExpressionResult of a node processExprNode() already processed
	 * into the given storage - the caller asserts the processing order by
	 * holding the very storage it processed the node into (a scope-based lookup
	 * would miss loop-convergence storages, which are never scope-visible).
	 * Throws when the node has no stored result.
	 */
	public function readStoredResult(Expr $expr, ExpressionResultStorage $storage): ExpressionResult
	{
		$result = $storage->findExpressionResult($expr);
		if ($result === null) {
			throw new ShouldNotHappenException(sprintf(
				'%s on line %d has no stored ExpressionResult - it was not processed by processExprNode().',
				get_class($expr),
				$expr->getStartLine(),
			));
		}

		return $result;
	}

	/**
	 * The type, on the given scope, of a node that may or may not have a stored
	 * ExpressionResult. Every call site of this method is UNDECIDED about whether
	 * the node was already analysed - each should eventually either consume the
	 * node's ExpressionResult where it was processed or be a synthetic node
	 * (processSyntheticOnDemand()).
	 */
	public function readTypeOfMaybeStored(Expr $expr, MutatingScope $scope): Type
	{
		$storage = $scope->getCurrentExpressionResultStorage();
		$result = $storage !== null ? $storage->findExpressionResult($expr) : null;
		if ($result !== null) {
			return $result->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		}

		return $this->readScopeStateOrSyntheticType($expr, $scope);
	}

	/**
	 * The type the scope itself knows for the expression, without any node
	 * processing: a string-named variable read is scope state (mirrors
	 * VariableHandler's typeCallback), and a type tracked for the whole
	 * expression answers directly - an on-demand walk would return that very
	 * holder anyway (the fresh result's beforeScope is the asking scope),
	 * after paying the walk. Null when the scope has no answer; the caller
	 * decides whether that means a synthetic walk (processSyntheticOnDemand())
	 * or an invariant violation.
	 */
	public function findScopeStateType(Expr $expr, MutatingScope $scope): ?Type
	{
		if ($expr instanceof Expr\Variable && is_string($expr->name)) {
			if ($scope->hasVariableType($expr->name)->no()) {
				return new ErrorType();
			}

			return $scope->getVariableType($expr->name);
		}

		// a literal is position-independent: the scope prices it without a walk,
		// so an argument the walk reaches only later (an IIFE's or a pipe's
		// operand) never has to be processed ahead of its turn
		if ($expr instanceof Node\Scalar\String_ || $expr instanceof Node\Scalar\Int_ || $expr instanceof Node\Scalar\Float_) {
			return $scope->getStateType($expr);
		}

		// A variable whose name is an expression ($$name) never reaches the read
		// above, and the scope tracks it like any other expression - so it belongs
		// here rather than falling through to a walk.
		if (
			!$expr instanceof Expr\Closure
			&& !$expr instanceof Expr\ArrowFunction
			&& $scope->hasExpressionType($expr)->yes()
		) {
			return TypeUtils::resolveLateResolvableTypes($scope->getTrackedExpressionType($expr));
		}

		return null;
	}

	/**
	 * The type, on the given scope, of a node the caller knows has no stored
	 * ExpressionResult in its walk: scope state (variable read / tracked
	 * holder) answers without a walk, anything else is priced as a synthetic
	 * node.
	 */
	public function readScopeStateOrSyntheticType(Expr $expr, MutatingScope $scope): Type
	{
		return $this->findScopeStateType($expr, $scope) ?? $this->processSyntheticOnDemand($expr, $scope)->getTypeOnScope($scope, $scope->nativeTypesPromoted);
	}

	/**
	 * The type the scope knows for an expression the caller has already pinned as
	 * tracked there (hasExpressionType() yes, or a string-named variable). Unlike
	 * readScopeStateOrSyntheticType() this never falls back to a synthetic walk -
	 * the caller decided that the scope answers.
	 */
	public function requireScopeStateType(Expr $expr, MutatingScope $scope): Type
	{
		$type = $this->findScopeStateType($expr, $scope);
		if ($type === null) {
			throw new ShouldNotHappenException(sprintf(
				'%s on line %d is not tracked on the scope it was pinned as tracked on.',
				get_class($expr),
				$expr->getStartLine(),
			));
		}

		return $type;
	}

	/**
	 * Fires the PHPSTAN_GUARD_NW diagnostic when a real (non-synthetic) AST node
	 * reaches an on-demand pricing path without having been processed and stored
	 * by processExprNode() first. Mirrors the guard in MutatingScope::getType():
	 * such a node should be answered from its stored ExpressionResult, never
	 * re-priced as if it were synthetic. Dormant unless PHPSTAN_GUARD_NW=1.
	 */
	private function guardAgainstUnprocessedRealNode(Expr $expr, string $caller): void
	{
		if (
			!self::$guardNewWorld
			|| !isset(self::$guardRealExprIds[spl_object_id($expr)])
			|| isset(self::$guardProcessedExprIds[spl_object_id($expr)])
		) {
			return;
		}

		throw new ShouldNotHappenException(sprintf(
			'%s() asked about non-synthetic %s on line %d before it was processed by processExprNode() - it should consume the node\'s ExpressionResult instead.',
			$caller,
			get_class($expr),
			$expr->getStartLine(),
		));
	}

	/**
	 * Processes a synthetic node (one an ExprHandler built itself) on a duplicate
	 * of the storage of the analysis currently in progress, mirroring
	 * MutatingScope::resolveTypeOfNewWorldHandlerNode(): the duplicate isolates
	 * the synthetic node's own stored result from the live storage while its real
	 * subnodes still resolve from the fallback.
	 */
	public function processSyntheticOnDemand(Expr $expr, MutatingScope $scope): ExpressionResult
	{
		$this->guardAgainstUnprocessedRealNode($expr, __FUNCTION__);
		$current = $scope->getCurrentExpressionResultStorage() ?? new ExpressionResultStorage();

		return $this->processExprOnDemand($expr, $scope, $current->duplicate());
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processExprNode(
		Node\Stmt $stmt,
		Expr $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
	): ExpressionResult
	{
		if ($this->returnStoredExpressionResults || $this->consumeStoredExpressionResults) {
			$storedResult = $storage->findExpressionResult($expr);
			// a stored result only answers when the current scope agrees with its
			// evaluation position on the variables the expression reads - a
			// counterfactual walk (an extension re-binding a variable and pricing
			// a real subtree, e.g. array_filter's per-element callback evaluation)
			// re-processes the node on its own scope instead. In CONSUME mode the
			// divergence is intentional (an ensured-non-null device) and the
			// stored result is consumed unconditionally, re-anchored below.
			if ($storedResult !== null && ($this->consumeStoredExpressionResults || $storedResult->askScopeVariableStateMatches($scope, $scope->nativeTypesPromoted))) {
				// a foreign-position answer must not thread its original walk
				// scopes into THIS walk - re-anchor it to the asking position so
				// subsequent operands keep evaluating on the asking scope
				if ($storedResult->getBeforeScope() === $scope) {
					return $storedResult;
				}

				$reanchored = $storedResult->atAskPosition($scope);
				if ($this->consumeStoredExpressionResults) {
					// the re-anchored view IS this walk's result for the node
					// (the nullsafe twin's receiver at the ensured position) -
					// store it so later asks (rules' storage reads) see the same
					// result the twin walk itself consumed, exactly like the
					// receiver walked inside the twin used to be stored
					$this->storeExpressionResult($storage, $expr, $reanchored);
				}

				return $reanchored;
			}
		}

		return $this->processExprNodeInternal($stmt, $expr, $scope, $storage, $nodeCallback, $context);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processExprNodeInternal(
		Node\Stmt $stmt,
		Expr $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
	): ExpressionResult
	{
		if ($expr instanceof Expr\CallLike && $expr->isFirstClassCallable()) {
			if ($expr instanceof FuncCall) {
				$newExpr = new FunctionCallableNode($expr->name, $expr);
			} elseif ($expr instanceof MethodCall) {
				$newExpr = new MethodCallableNode($expr->var, $expr->name, $expr);
			} elseif ($expr instanceof StaticCall) {
				$newExpr = new StaticMethodCallableNode($expr->class, $expr->name, $expr);
			} elseif ($expr instanceof New_ && !$expr->class instanceof Class_) {
				$newExpr = new InstantiationCallableNode($expr->class, $expr);
			} else {
				throw new ShouldNotHappenException();
			}

			$newExprResult = $this->processExprNode($stmt, $newExpr, $scope, $storage, $nodeCallback, $context);
			$expressionResult = $this->expressionResultFactory->create(
				$newExprResult->getScope(),
				beforeScope: $scope,
				expr: $expr,
				hasYield: $newExprResult->hasYield(),
				isAlwaysTerminating: $newExprResult->isAlwaysTerminating(),
				throwPoints: $newExprResult->getThrowPoints(),
				impurePoints: $newExprResult->getImpurePoints(),
				variableFlow: $newExprResult->getVariableFlow(),
				// the first-class callable closure type lives on the *CallableNode
				// result; delegate so getType() of the original CallLike answers from it
				typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $newExprResult->getNativeType() : $newExprResult->getType()),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			);
			$this->storeExpressionResult($storage, $expr, $expressionResult);
			return $expressionResult;
		}

		$exprHandler = ExprHandlerRegistry::resolve($expr, $this->container);
		if ($exprHandler !== null) {
			$expressionResult = $exprHandler->processExpr($this, $stmt, $expr, $scope, $storage, $nodeCallback, $context);
			// a chain link an enclosing isset/empty/?? could not device ahead
			// of its walk (an untracked call) is deviced now, from the type
			// the walk produced
			$expressionResult = $this->getNonNullabilityHelper()->applyPendingEnsure($expr, $expressionResult);
			$this->storeExpressionResult($storage, $expr, $expressionResult);
			// Force potential producers before collecting the body's constraints.
			// Type reads only construct markers; they never register sites as a side effect.
			$frame = $scope->getCurrentTemplateArgumentFrame();
			if (
				$frame !== null && $frame->isObserving()
				&& $expr instanceof Expr\CallLike
			) {
				$constraints = $this->templateArgumentObserver->collectSites($expressionResult->getType());
				$expressionResult = $expressionResult->withScope($expressionResult->getScope()->addTemplateArgumentConstraints($constraints));
				$this->storeExpressionResult($storage, $expr, $expressionResult);
			}
			// The node's own callback fires AFTER its result is stored, with the
			// scope captured before processing. Rules observe the same (scope,
			// answer) pair as at a pre-order emission - previously a pre-order
			// rule parks on its first ask and resumes at this store anyway - but
			// a synchronously invoked rule (the plain resolver, PHP < 8.1) now
			// finds the node's and its subtree's results in the storage instead
			// of re-walking them on demand.
			$this->callNodeCallbackWithExpression($nodeCallback, $expr, $scope, $storage, $context);
			// the call is now processed and stored; emit a virtual node so
			// impossible-check rules read its specified types from the result
			// instead of asking the scope before the call node is processed
			if ($expr instanceof FuncCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new FunctionCallExpressionNode($expr, $expressionResult, $expressionResult->getArgsResult()), $scope, $storage, $context);
			} elseif ($expr instanceof MethodCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new MethodCallExpressionNode($expr, $expressionResult, $expressionResult->getArgsResult()), $scope, $storage, $context);
			} elseif ($expr instanceof StaticCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new StaticMethodCallExpressionNode($expr, $expressionResult, $expressionResult->getArgsResult()), $scope, $storage, $context);
			}
			return $expressionResult;
		}

		throw new ShouldNotHappenException(sprintf('Unhandled expr: %s', get_class($expr)));
	}

	/**
	 * @return string[]
	 */
	public function getAssignedVariables(Expr $expr): array
	{
		if ($expr instanceof Expr\Variable) {
			if (is_string($expr->name)) {
				return [$expr->name];
			}

			return [];
		}

		if ($expr instanceof Expr\List_) {
			$names = [];
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				$names = array_merge($names, $this->getAssignedVariables($item->value));
			}

			return $names;
		}

		if ($expr instanceof ArrayDimFetch) {
			return $this->getAssignedVariables($expr->var);
		}

		return [];
	}

	private const REPLAYABLE_BODY_ATTRIBUTE = 'convergenceReplayableBody';

	/**
	 * Whether a recorded convergence pass over the loop body can replace the
	 * final walk. A pass runs at deep statement context, the final walk at top
	 * level - constructs that analyse differently between the two (nested
	 * loop/label fixpoints run only at top level, statement-level classes are
	 * skipped at deep context) disqualify the body. Closure bodies process
	 * context-independently and are not traversed.
	 *
	 * @param Node\Stmt[] $bodyStmts
	 */
	public function isReplayableConvergenceBody(Node $loopNode, array $bodyStmts): bool
	{
		$cached = $loopNode->getAttribute(self::REPLAYABLE_BODY_ATTRIBUTE);
		if ($cached !== null) {
			return $cached;
		}

		$replayable = true;
		foreach ($bodyStmts as $bodyStmt) {
			if ($this->hasContextSensitiveConstruct($bodyStmt)) {
				$replayable = false;
				break;
			}
		}
		$loopNode->setAttribute(self::REPLAYABLE_BODY_ATTRIBUTE, $replayable);

		return $replayable;
	}

	private function hasContextSensitiveConstruct(Node $node): bool
	{
		if ($node instanceof Expr\Closure) {
			return false;
		}
		if (
			$node instanceof Node\Stmt\While_
			|| $node instanceof Node\Stmt\Do_
			|| $node instanceof Node\Stmt\For_
			|| $node instanceof Foreach_
			|| $node instanceof Node\Stmt\Label
			|| $node instanceof Node\Stmt\ClassLike
		) {
			return true;
		}

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->$subNodeName;
			if ($subNode instanceof Node) {
				if ($this->hasContextSensitiveConstruct($subNode)) {
					return true;
				}
			} elseif (is_array($subNode)) {
				foreach ($subNode as $item) {
					if ($item instanceof Node && $this->hasContextSensitiveConstruct($item)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Opens an engine-feeding gatherer frame for the duration of a body walk.
	 * The caller closes it in a finally block via popNodeGatherer().
	 *
	 * @param callable(Node, Scope): void $gatherer
	 */
	public function pushNodeGatherer(callable $gatherer): void
	{
		$this->nodeGatherers[] = $gatherer;
	}

	public function popNodeGatherer(): void
	{
		array_pop($this->nodeGatherers);
	}

	/**
	 * Detaches all gatherer frames for a walk that must not feed them. The
	 * caller reattaches them in a finally block via restoreNodeGatherers().
	 *
	 * @return list<callable(Node, Scope): void>
	 */
	public function suspendNodeGatherers(): array
	{
		$gatherers = $this->nodeGatherers;
		$this->nodeGatherers = [];

		return $gatherers;
	}

	/**
	 * @param list<callable(Node, Scope): void> $gatherers
	 */
	public function restoreNodeGatherers(array $gatherers): void
	{
		$this->nodeGatherers = $gatherers;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function replayRecording(RecordingNodeCallback $recording, callable $nodeCallback, ExpressionResultStorage $storage, MutatingScope $scope): void
	{
		$this->replayRecordingRange($recording, 0, $recording->count(), $nodeCallback, $storage, $scope);
	}

	/**
	 * Replays the recorded pairs [$from, $to) the way callNodeCallback() would
	 * have emitted them: gatherer frames observe them exactly like live ones
	 * (with the raw walk scope), a real callback gets the callback scope - and
	 * a recording callback records them again (a loop's fixpoint replay running
	 * inside a body's observation pass).
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function replayRecordingRange(RecordingNodeCallback $recording, int $from, int $to, callable $nodeCallback, ExpressionResultStorage $storage, MutatingScope $scope): void
	{
		$pairs = $recording->getPairs();
		$scope->pushExpressionResultStorage($storage);
		try {
			for ($i = $from; $i < $to; $i++) {
				[$node, $pairScope] = $pairs[$i];
				if (!$pairScope instanceof MutatingScope) {
					throw new ShouldNotHappenException();
				}
				$this->callNodeCallback($nodeCallback, $node, $pairScope, $storage);
			}
		} finally {
			$scope->popExpressionResultStorage();
		}
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function callNodeCallbackWithExpression(
		callable $nodeCallback,
		Node $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		ExpressionContext $context,
	): void
	{
		if ($context->isDeep()) {
			$scope = $scope->exitFirstLevelStatements();
		}
		$this->callNodeCallback($nodeCallback, $expr, $scope, $storage);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function callNodeCallback(
		callable $nodeCallback,
		Node $node,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
	): void
	{
		// Engine-feeding gatherer frames observe the node at the emission
		// position - their arrays are read as soon as the enclosing body walk
		// returns. Gatherers are engine code and never ask about types -
		// handing them the raw scope skips a NodeCallbackScope construction per
		// emission; the scopes they capture (return statements, impure points)
		// answer later asks through the storage hub like any MutatingScope.
		foreach ($this->nodeGatherers as $gatherer) {
			$gatherer($node, $scope);
		}

		if ($nodeCallback instanceof NoopNodeCallback) {
			return;
		}

		if ($nodeCallback instanceof RecordingNodeCallback) {
			// recording never asks about types - the pairs are wrapped and
			// bound to the storage at replay time instead
			$nodeCallback($node, $scope);
			return;
		}

		// post-order emission means the node's own result and every subnode
		// result are already stored when the callback fires - NodeCallbackScope
		// answers every ask synchronously from the storage
		$nodeCallback($node, $scope->toNodeCallbackScope());
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processClosureNode(
		Node\Stmt $stmt,
		Expr\Closure $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
		?Type $nativePassedToType = null,
	): ProcessClosureResult
	{
		return $this->processClosureNodeInternal($stmt, $expr, $scope, $storage, $nodeCallback, $context, $passedToType, $nativePassedToType);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processClosureNodeInternal(
		Node\Stmt $stmt,
		Expr\Closure $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
		?Type $nativePassedToType = null,
	): ProcessClosureResult
	{
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}

		$byRefUses = [];

		$closureCallArgs = $expr->getAttribute(ClosureArgVisitor::ATTRIBUTE_NAME);
		$parameterTypes = $this->container->getByType(ClosureParameterResolver::class)->resolve($scope, $expr, $storage, $closureCallArgs, $passedToType, $nativePassedToType);
		$callableParameters = $parameterTypes->parameters;
		$nativeCallableParameters = $parameterTypes->nativeParameters;

		$useScope = $scope;
		foreach ($expr->uses as $use) {
			if ($use->byRef) {
				$byRefUses[] = $use;
				$useScope = $useScope->enterExpressionAssign($use->var);

				$inAssignRightSideVariableName = $context->getInAssignRightSideVariableName();
				$inAssignRightSideExpr = $context->getInAssignRightSideExpr();
				if (
					$inAssignRightSideVariableName === $use->var->name
					&& $inAssignRightSideExpr !== null
				) {
					// a call's type is carried by the context (see
					// ExpressionContext::enterAssignRightSideCallArgs()); a closure
					// right side resolves through the closure type resolver
					$inAssignRightSideType = $context->getInAssignRightSideType() ?? $this->resolveCallableTypeForScope($inAssignRightSideExpr, $scope);
					if ($inAssignRightSideType instanceof ClosureType) {
						$variableType = $inAssignRightSideType;
					} else {
						$alreadyHasVariableType = $scope->hasVariableType($inAssignRightSideVariableName);
						if ($alreadyHasVariableType->no()) {
							$variableType = TypeCombinator::union(new NullType(), $inAssignRightSideType);
						} else {
							$variableType = TypeCombinator::union($scope->getVariableType($inAssignRightSideVariableName), $inAssignRightSideType);
						}
					}
					$inAssignRightSideNativeType = $context->getInAssignRightSideNativeType() ?? $this->resolveCallableTypeForScope($inAssignRightSideExpr, $scope->doNotTreatPhpDocTypesAsCertain());
					if ($inAssignRightSideNativeType instanceof ClosureType) {
						$variableNativeType = $inAssignRightSideNativeType;
					} else {
						$alreadyHasVariableType = $scope->hasVariableType($inAssignRightSideVariableName);
						if ($alreadyHasVariableType->no()) {
							$variableNativeType = TypeCombinator::union(new NullType(), $inAssignRightSideNativeType);
						} else {
							$variableNativeType = TypeCombinator::union($scope->getVariableType($inAssignRightSideVariableName), $inAssignRightSideNativeType);
						}
					}
					$scope = $scope->assignVariable($inAssignRightSideVariableName, $variableType, $variableNativeType, TrinaryLogic::createYes());
				}
			}
			$this->processExprNode($stmt, $use->var, $useScope, $storage, $nodeCallback, $context->withoutValueFlow());
			if (!$use->byRef) {
				continue;
			}

			$useScope = $useScope->exitExpressionAssign($use->var);
		}

		if ($expr->returnType !== null) {
			$this->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters, $nativeCallableParameters);
		$closureScope = $closureScope->processClosureScope($scope, null, $byRefUses);
		$closureType = $closureScope->getAnonymousFunctionReflection();
		if (!$closureType instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$this->callNodeCallback($nodeCallback, new InClosureNode($closureType, $expr), $closureScope, $storage);

		$executionEnds = [];
		$gatheredReturnStatements = [];
		$gatheredReturnStatementsAfterFinally = [];
		$gatheredReturnStatementsWithScope = [];
		$gatheredYieldStatements = [];
		$gatheredYieldStatementsWithScope = [];
		$closureImpurePoints = [];
		$invalidateExpressions = [];
		$closureStmtsGatherer = static function (Node $node, Scope $scope) use (&$executionEnds, &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally, &$gatheredReturnStatementsWithScope, &$gatheredYieldStatements, &$gatheredYieldStatementsWithScope, &$closureScope, &$closureImpurePoints, &$invalidateExpressions): void {
			if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
				return;
			}
			if ($node instanceof PropertyAssignNode) {
				$closureImpurePoints[] = new ImpurePoint(
					$scope,
					$node,
					'propertyAssign',
					'property assignment',
					true,
				);
				$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
				return;
			}
			if ($node instanceof ExecutionEndNode) {
				$executionEnds[] = $node;
				return;
			}
			if ($node instanceof ReturnAfterFinallyNode) {
				$gatheredReturnStatementsAfterFinally[] = new ReturnStatement($scope, $node->getReturnNode());
				return;
			}
			if ($node instanceof InvalidateExprNode) {
				$invalidateExpressions[] = $node;
				return;
			}
			if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
				$gatheredYieldStatements[] = $node;
				$gatheredYieldStatementsWithScope[] = [$node, $scope];
			}
			if (!$node instanceof Return_) {
				return;
			}

			$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			$gatheredReturnStatementsWithScope[] = [$node, $scope];
		};

		if (count($byRefUses) === 0) {
			$this->pushNodeGatherer($closureStmtsGatherer);
			try {
				$statementResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $nodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments()));
			} finally {
				$this->popNodeGatherer();
			}
			$publicStatementResult = $statementResult->toPublic();
			$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions, $storage);
			$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredReturnStatementsAfterFinally,
				$gatheredYieldStatements,
				$publicStatementResult,
				$executionEnds,
				array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
			), $closureReturnStatementsNodeScope, $storage);
			$this->callNodeCallback($nodeCallback, VariableLivenessResolver::resolve($expr, $statementResult->getVariableFlow()), $closureReturnStatementsNodeScope, $storage);

			return new ProcessClosureResult(
				$scope->addTemplateArgumentConstraints($statementResult->getScope()->getTemplateArgumentConstraints()),
				$statementResult->getThrowPoints(),
				$statementResult->getImpurePoints(),
				$invalidateExpressions,
				$gatheredReturnStatementsWithScope,
				$gatheredYieldStatementsWithScope,
				$executionEnds,
				array_merge($closureImpurePoints, $statementResult->getImpurePoints()),
			);
		}

		$originalStorage = $storage;

		$count = 0;
		$closureResultScope = null;
		$replayBodyRecording = null;
		$replayPassStorage = null;
		$replayPassResult = null;
		$replayEntryScope = null;
		$bodyIsReplayable = $this->isReplayableConvergenceBody($expr, $expr->stmts);
		do {
			$prevScope = $closureScope;

			$storage = $originalStorage->duplicate();
			$bodyRecording = $bodyIsReplayable ? new RecordingNodeCallback() : new NoopNodeCallback();
			// deep context, like the loop handlers' own convergence passes: inner
			// loops walk single-pass here and only the final walk below (top-level)
			// runs their full convergence - otherwise every closure-convergence
			// pass would re-converge every inner loop from scratch
			$intermediaryClosureScopeResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $bodyRecording, StatementContext::createDeep(resolveTemplateArguments: false));
			// the candidate to replace the final walk when this pass's entry
			// turns out to be the fixpoint
			if ($bodyRecording instanceof RecordingNodeCallback) {
				$replayBodyRecording = $bodyRecording;
				$replayPassStorage = $storage;
				$replayPassResult = $intermediaryClosureScopeResult;
				$replayEntryScope = $prevScope;
			}
			$intermediaryClosureScope = $intermediaryClosureScopeResult->getScope();
			foreach ($intermediaryClosureScopeResult->getExitPoints() as $exitPoint) {
				$intermediaryClosureScope = $intermediaryClosureScope->mergeWith($exitPoint->getScope());
			}

			if ($expr->getAttribute(ImmediatelyInvokedClosureVisitor::ATTRIBUTE_NAME) === true) {
				$closureResultScope = $intermediaryClosureScope;
				break;
			}

			$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters, $nativeCallableParameters);
			$closureScope = $closureScope->processClosureScope($intermediaryClosureScope, $prevScope, $byRefUses);

			if ($closureScope->equals($prevScope)) {
				break;
			}
			if ($count >= self::GENERALIZE_AFTER_ITERATION) {
				$closureScope = $prevScope->generalizeWith($closureScope);
			}
			$count++;
		} while ($count < self::LOOP_SCOPE_ITERATIONS);

		if ($closureResultScope === null) {
			$closureResultScope = $closureScope;
		}

		$storage = $originalStorage;
		$this->pushNodeGatherer($closureStmtsGatherer);
		try {
			if (
				$replayBodyRecording !== null && $replayPassStorage !== null
				&& $replayPassResult !== null && $replayEntryScope !== null
				&& $closureScope->equals($replayEntryScope)
			) {
				// the final walk would repeat the recorded fixpoint pass exactly
				// (same entry scope, deterministic walk) - adopt the pass's result
				// and replay its emissions through the real callback instead.
				// The pass's own entry scope takes over: the recorded pairs carry
				// its anonymous-function reflection, which the gatherer's filter
				// compares by identity (the state is equals-identical anyway).
				$closureScope = $replayEntryScope;
				$originalStorage->mergeResults($replayPassStorage);
				$this->replayRecording($replayBodyRecording, $nodeCallback, $originalStorage, $closureScope);
				$statementResult = $replayPassResult;
			} else {
				$statementResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $nodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments()));
			}
		} finally {
			$this->popNodeGatherer();
		}
		$publicStatementResult = $statementResult->toPublic();
		$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions, $storage);
		$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredReturnStatementsAfterFinally,
			$gatheredYieldStatements,
			$publicStatementResult,
			$executionEnds,
			array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
		), $closureReturnStatementsNodeScope, $storage);
		$this->callNodeCallback($nodeCallback, VariableLivenessResolver::resolve($expr, $statementResult->getVariableFlow()), $closureReturnStatementsNodeScope, $storage);

		return new ProcessClosureResult(
			$scope->addTemplateArgumentConstraints($statementResult->getScope()->getTemplateArgumentConstraints()),
			$statementResult->getThrowPoints(),
			$statementResult->getImpurePoints(),
			$invalidateExpressions,
			$gatheredReturnStatementsWithScope,
			$gatheredYieldStatementsWithScope,
			$executionEnds,
			array_merge($closureImpurePoints, $statementResult->getImpurePoints()),
			$closureResultScope,
			$byRefUses,
		);
	}

	/**
	 * The closure scope was entered with a shallow reflection (parameters +
	 * declared return, no body walk - see ClosureTypeResolver::getClosureType()
	 * with $shallow). Now that the single body walk has gathered the returns,
	 * build the refined ClosureType from them (no second walk) and swap it onto
	 * the scope the ClosureReturnStatementsNode fires with, so the return-type
	 * rules see the refined expected return (e.g. Bar&Foo, not just Foo).
	 *
	 * @param list<array{Return_, Scope}> $gatheredReturnStatementsWithScope
	 * @param list<array{Expr\Yield_|Expr\YieldFrom, Scope}> $gatheredYieldStatementsWithScope
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	private function refineClosureNodeScope(
		MutatingScope $closureScope,
		MutatingScope $scope,
		Expr\Closure $expr,
		array $gatheredReturnStatementsWithScope,
		array $gatheredYieldStatementsWithScope,
		array $executionEnds,
		array $throwPoints,
		array $impurePoints,
		array $invalidateExpressions,
		ExpressionResultStorage $storage,
	): MutatingScope
	{
		$refinedClosureType = $this->container->getByType(ClosureTypeResolver::class)->buildClosureTypeForClosure(
			$scope,
			$expr,
			$gatheredReturnStatementsWithScope,
			$gatheredYieldStatementsWithScope,
			$executionEnds,
			$throwPoints,
			$impurePoints,
			$invalidateExpressions,
			false,
			$storage,
		);

		return $closureScope->withAnonymousFunctionReflection($refinedClosureType);
	}

	/**
	 * @param InvalidateExprNode[] $invalidatedExpressions
	 * @param string[] $uses
	 */
	public function processImmediatelyCalledCallable(MutatingScope $scope, array $invalidatedExpressions, array $uses): MutatingScope
	{
		if ($scope->isInClass()) {
			$uses[] = 'this';
		}

		$finder = new NodeFinder();
		foreach ($invalidatedExpressions as $invalidateExpression) {
			$result = $finder->findFirst([$invalidateExpression->getExpr()], static fn ($node) => $node instanceof Variable && in_array($node->name, $uses, true));
			if ($result === null) {
				continue;
			}

			$requireMoreCharacters = $invalidateExpression->getExpr() instanceof Variable;
			$scope = $scope->invalidateExpression($invalidateExpression->getExpr(), $requireMoreCharacters);
		}

		return $scope;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processArrowFunctionNode(
		Node\Stmt $stmt,
		Expr\ArrowFunction $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		?Type $passedToType,
		?Type $nativePassedToType = null,
		?ExpressionContext $context = null,
	): ProcessArrowFunctionResult
	{
		$context ??= ExpressionContext::createTopLevel();
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}
		if ($expr->returnType !== null) {
			$this->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$arrowFunctionCallArgs = $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME);
		$parameterTypes = $this->container->getByType(ClosureParameterResolver::class)->resolve($scope, $expr, $storage, $arrowFunctionCallArgs, $passedToType, $nativePassedToType);
		$callableParameters = $parameterTypes->parameters;
		$nativeCallableParameters = $parameterTypes->nativeParameters;
		$arrowFunctionScope = $scope->enterArrowFunction($expr, $callableParameters, $nativeCallableParameters);
		if ($arrowFunctionScope->getAnonymousFunctionReflection() === null) {
			throw new ShouldNotHappenException();
		}

		// Gather the property-assign impure points and invalidate expressions the
		// arrow function type needs (mirroring ClosureTypeResolver::getClosureType()),
		// on top of the regular rule node callback, so the single body walk here
		// feeds ClosureTypeResolver::buildClosureTypeForArrowFunction().
		$arrowFunctionImpurePoints = [];
		$invalidateExpressions = [];
		$arrowFunctionStmtsGatherer = static function (Node $node, Scope $innerScope) use ($arrowFunctionScope, &$arrowFunctionImpurePoints, &$invalidateExpressions): void {
			if ($innerScope->getAnonymousFunctionReflection() !== $arrowFunctionScope->getAnonymousFunctionReflection()) {
				return;
			}

			if ($node instanceof InvalidateExprNode) {
				$invalidateExpressions[] = $node;
				return;
			}

			if (!$node instanceof PropertyAssignNode) {
				return;
			}

			$arrowFunctionImpurePoints[] = new ImpurePoint(
				$innerScope,
				$node,
				'propertyAssign',
				'property assignment',
				true,
			);
			$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
		};

		$this->pushNodeGatherer($arrowFunctionStmtsGatherer);
		try {
			$exprResult = $this->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $storage, $nodeCallback, ExpressionContext::createTopLevel($context->shouldResolveTemplateArguments()));
		} finally {
			$this->popNodeGatherer();
		}
		$scope = $scope->addTemplateArgumentConstraints($exprResult->getScope()->getTemplateArgumentConstraints());
		$scope = $scope->addTemplateArgumentConstraints($this->collectReturnSend($arrowFunctionScope, $exprResult));

		$closureTypeThrowPoints = array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->toPublic(), $exprResult->getThrowPoints());
		$closureTypeImpurePoints = array_merge($arrowFunctionImpurePoints, $exprResult->getImpurePoints());

		// The arrow scope was entered with a shallow reflection (parameters +
		// declared return, no body walk). Now that the single body walk above has
		// run, build the refined arrow function type from the body expression's
		// stored type (no second walk) and fire InArrowFunctionNode with it, so the
		// node and the return-type rules see the refined expected return.
		$refinedArrowFunctionType = $this->container->getByType(ClosureTypeResolver::class)->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowFunctionScope,
			$closureTypeThrowPoints,
			$closureTypeImpurePoints,
			$invalidateExpressions,
			false,
			$storage,
		);
		$refinedArrowFunctionScope = $arrowFunctionScope->withAnonymousFunctionReflection($refinedArrowFunctionType);
		$this->callNodeCallback($nodeCallback, new InArrowFunctionNode($refinedArrowFunctionType, $expr), $refinedArrowFunctionScope, $storage);

		return new ProcessArrowFunctionResult(
			$this->expressionResultFactory->create(
				$scope,
				beforeScope: $scope,
				expr: $expr,
				hasYield: false,
				isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
				variableFlow: ArrowFunctionHandler::getVariableFlow($expr, $exprResult),
				throwPoints: $exprResult->getThrowPoints(),
				impurePoints: $exprResult->getImpurePoints(),
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			),
			$arrowFunctionScope,
			$closureTypeThrowPoints,
			$closureTypeImpurePoints,
			$invalidateExpressions,
		);
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @return ParameterReflection[]|null
	 */
	public function createCallableParameters(MutatingScope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType): ?array
	{
		return $this->doCreateCallableParameters($scope, $closureExpr, $args, $passedToType, fn (MutatingScope $s, Expr $e): Type => $this->resolveCallableTypeForScope($e, $s));
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @return ParameterReflection[]|null
	 */
	public function createNativeCallableParameters(MutatingScope $scope, Expr $closureExpr, ?array $args, ?Type $nativePassedToType): ?array
	{
		return $this->doCreateCallableParameters($scope, $closureExpr, $args, $nativePassedToType, fn (MutatingScope $s, Expr $e): Type => $this->resolveCallableTypeForScope($e, $s->doNotTreatPhpDocTypesAsCertain()));
	}

	/**
	 * Resolves the type of an expression a callable parameter is derived from -
	 * either the closure/arrow function whose acceptors describe the parameters,
	 * or a call argument refining them. A closure/arrow function is resolved
	 * directly through ClosureTypeResolver (as Scope::getType() would), not by
	 * processing it on demand: createCallableParameters() runs while that very
	 * closure is being processed, so on-demand processing would re-enter
	 * processClosureNodeInternal() endlessly.
	 */
	public function resolveCallableTypeForScope(Expr $expr, MutatingScope $scope): Type
	{
		if ($expr instanceof Expr\Closure || $expr instanceof Expr\ArrowFunction) {
			return $this->container->getByType(ClosureTypeResolver::class)->getClosureType($scope, $expr, false, $scope->getCurrentExpressionResultStorage());
		}

		return $this->readTypeOfMaybeStored($expr, $scope);
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @param Closure(MutatingScope, Expr): Type $typeGetter
	 * @return ParameterReflection[]|null
	 */
	private function doCreateCallableParameters(MutatingScope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType, Closure $typeGetter): ?array
	{
		$callableParameters = null;
		if ($args !== null) {
			$closureType = $typeGetter($scope, $closureExpr);

			if ($closureType->isCallable()->no()) {
				return null;
			}

			$acceptors = $closureType->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 1) {
				$callableParameters = $acceptors[0]->getParameters();

				foreach ($callableParameters as $index => $callableParameter) {
					if (!isset($args[$index])) {
						continue;
					}

					if ($callableParameter->isVariadic()) {
						$argTypes = [];
						$argNumber = count($args);
						for ($j = $index; $j < $argNumber; $j++) {
							$argTypes[] = $typeGetter($scope, $args[$j]->value);
						}
						$type = TypeCombinator::union(...$argTypes);
					} else {
						$type = $typeGetter($scope, $args[$index]->value);
					}
					$callableParameters[$index] = new NativeParameterReflection(
						$callableParameter->getName(),
						$callableParameter->isOptional(),
						$type,
						$callableParameter->passedByReference(),
						$callableParameter->isVariadic(),
						$callableParameter->getDefaultValue(),
					);
				}
			}
		} elseif ($passedToType !== null && !$passedToType->isCallable()->no()) {
			if ($passedToType instanceof UnionType) {
				$passedToType = $passedToType->filterTypes(static fn (Type $innerType) => $innerType->isCallable()->yes());

				if ($passedToType->isCallable()->no()) {
					return null;
				}
			}

			$acceptors = $passedToType->getCallableParametersAcceptors($scope);
			foreach ($acceptors as $acceptor) {
				$acceptorParameters = array_map(static fn (ParameterReflection $callableParameter) => new NativeParameterReflection(
					$callableParameter->getName(),
					$callableParameter->isOptional(),
					$callableParameter->getType(),
					$callableParameter->passedByReference(),
					$callableParameter->isVariadic(),
					$callableParameter->getDefaultValue(),
				), $acceptor->getParameters());

				if ($callableParameters === null) {
					$callableParameters = $acceptorParameters;
					continue;
				}

				$newParameters = [];
				$parameterCount = max(count($callableParameters), count($acceptorParameters));
				for ($i = 0; $i < $parameterCount; $i++) {
					if (!array_key_exists($i, $acceptorParameters)) {
						$newParameters[] = $callableParameters[$i]->toOptional();
						continue;
					}

					if (!array_key_exists($i, $callableParameters)) {
						$newParameters[] = $acceptorParameters[$i]->toOptional();
						continue;
					}

					$newParameters[] = $callableParameters[$i]->union($acceptorParameters[$i]);
				}

				$callableParameters = $newParameters;
			}
		}

		return $callableParameters;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processParamNode(
		Node\Stmt $stmt,
		Node\Param $param,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
	): void
	{
		$this->processAttributeGroups($stmt, $param->attrGroups, $scope, $storage, $nodeCallback);
		$this->callNodeCallback($nodeCallback, $param, $scope, $storage);
		if ($param->type !== null) {
			$this->callNodeCallback($nodeCallback, $param->type, $scope, $storage);
		}
		if ($param->default === null) {
			return;
		}

		$this->processExprNode($stmt, $param->default, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
	}

	/**
	 * @param AttributeGroup[] $attrGroups
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processAttributeGroups(
		Node\Stmt $stmt,
		array $attrGroups,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
	): void
	{
		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$className = $scope->resolveName($attr->name);
				if ($this->reflectionProvider->hasClass($className)) {
					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasConstructor()) {
						$constructorReflection = $classReflection->getConstructor();
						$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization(
							$attr->args,
							$constructorReflection->getVariants(),
							$constructorReflection->getNamedArgumentsVariants(),
						);
						$expr = new New_($attr->name, $attr->args);
						$expr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
						$this->argumentsHandler->processArgs($this, $stmt, $constructorReflection, null, $constructorReflection->getVariants(), $constructorReflection->getNamedArgumentsVariants(), $expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
						$this->callNodeCallback($nodeCallback, $attr, $scope, $storage);
						continue;
					}
				}

				foreach ($attr->args as $arg) {
					$this->processExprNode($stmt, $arg->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
					$this->callNodeCallback($nodeCallback, $arg, $scope, $storage);
				}
				$this->callNodeCallback($nodeCallback, $attr, $scope, $storage);
			}
			$this->callNodeCallback($nodeCallback, $attrGroup, $scope, $storage);
		}
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processVirtualAssign(MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, Expr $var, Expr $assignedExpr, callable $nodeCallback, ?ExpressionResult $assignedExprResult = null): ExpressionResult
	{
		// work off an available result for the assigned expr: passed by the
		// caller, or fabricated from a type-carrying virtual node - threaded
		// straight into applyWrite() so its reads compose instead of falling
		// back to on-demand pricing of the type, the truthy/falsey narrowing,
		// and the synthetic sentinel comparisons
		if (
			$assignedExprResult === null
			&& ($assignedExpr instanceof TypeExpr || $assignedExpr instanceof NativeTypeExpr)
			&& $storage->findExpressionResult($assignedExpr) === null
		) {
			$assignedExprResult = $this->container->getByType(VirtualExprResultHelper::class)->createTypeExprResult($scope, $assignedExpr);
		}

		$assignHandler = $this->container->getByType(AssignHandler::class);
		$virtualAssignNodeCallback = VirtualAssignNodeCallback::create($nodeCallback);
		$target = $assignHandler->prepareTarget(
			$this,
			$scope,
			$storage,
			$stmt,
			$var,
			$assignedExpr,
			$virtualAssignNodeCallback,
			ExpressionContext::createDeep(),
			AssignTargetWalkMode::virtualAssign(),
		);

		return $assignHandler->applyWrite(
			$this,
			$target,
			$this->expressionResultFactory->create(
				$target->getScope(),
				beforeScope: $target->getScope(),
				expr: $assignedExpr,
				hasYield: false,
				isAlwaysTerminating: false,
				throwPoints: [],
				impurePoints: [],
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			),
			$assignedExprResult,
			$stmt,
			$storage,
			$virtualAssignNodeCallback,
			ExpressionContext::createDeep(),
		);
	}

	/**
	 * The template argument frame of the body being walked while it observes
	 * a body that created unresolved template arguments - null otherwise, so
	 * every observation hook costs a null check outside the observation pass.
	 */
	public function observingTemplateArgumentFrame(MutatingScope $scope): ?TemplateArgumentFrame
	{
		$frame = $scope->getCurrentTemplateArgumentFrame();
		if ($frame === null || !$frame->isObserving() || $scope->getTemplateArgumentConstraints() === null) {
			return null;
		}

		return $frame;
	}

	/**
	 * A value leaves the function: the declared return type is a send target for
	 * the unresolved template arguments it carries.
	 */
	public function collectReturnSend(MutatingScope $scope, ExpressionResult $returnedResult): TemplateArgumentConstraints
	{
		$frame = $this->observingTemplateArgumentFrame($returnedResult->getScope());
		if ($frame === null) {
			return TemplateArgumentConstraints::createEmpty();
		}
		if ($scope->isInAnonymousFunction()) {
			$declaredReturnType = $scope->getAnonymousFunctionReturnType();
		} else {
			$function = $scope->getFunction();
			$declaredReturnType = $function !== null ? $function->getReturnType() : null;
		}
		if ($declaredReturnType === null) {
			return TemplateArgumentConstraints::createEmpty();
		}

		return $this->templateArgumentObserver->collectSend($declaredReturnType, $returnedResult->getType());
	}

	/**
	 * A write through ArrayAccess (`$storage[$key] = $value`) reaches the
	 * object's template arguments exactly like the offsetSet() call it stands
	 * for, so observe both the key and the written value against that method's
	 * parameters resolved on the receiver.
	 */
	public function collectOffsetSetUsage(MutatingScope $scope, Type $receiverType, ?Type $keyType, Type $valueType): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		$frame = $this->observingTemplateArgumentFrame($scope);
		if ($frame === null || !$receiverType->hasMethod('offsetSet')->yes()) {
			return $constraints;
		}

		$parameters = $receiverType->getMethod('offsetSet', $scope)->getOnlyVariant()->getParameters();
		if ($keyType !== null && isset($parameters[0])) {
			$constraints = $constraints->merge($this->templateArgumentObserver->collectArgument($parameters[0]->getType(), $keyType));
		}
		if (!isset($parameters[1])) {
			return $constraints;
		}

		$constraints = $constraints->merge($this->templateArgumentObserver->collectArgument($parameters[1]->getType(), $valueType));
		return $constraints;
	}

}
