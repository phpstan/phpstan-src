<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ExprHandler\AssignHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
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
use PHPStan\Node\PropertyHookStatementNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Node\StaticMethodCallExpressionNode;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Parser\ArrowFunctionArgVisitor;
use PHPStan\Parser\ClosureArgVisitor;
use PHPStan\Parser\GotoLabelVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function count;
use function get_class;
use function getenv;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function max;
use function spl_object_id;
use function sprintf;
use function usort;

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
	 * @param ExtensionsCollection<FunctionParameterOutTypeExtension> $functionParameterOutTypeExtensions
	 * @param ExtensionsCollection<MethodParameterOutTypeExtension> $methodParameterOutTypeExtensions
	 * @param ExtensionsCollection<StaticMethodParameterOutTypeExtension> $staticMethodParameterOutTypeExtensions
	 * @param ExtensionsCollection<ReadWritePropertiesExtension> $readWritePropertiesExtensions
	 * @param ExtensionsCollection<FunctionParameterClosureThisExtension> $functionParameterClosureThisExtensions
	 * @param ExtensionsCollection<MethodParameterClosureThisExtension> $methodParameterClosureThisExtensions
	 * @param ExtensionsCollection<StaticMethodParameterClosureThisExtension> $staticMethodParameterClosureThisExtensions
	 * @param ExtensionsCollection<FunctionParameterClosureTypeExtension> $functionParameterClosureTypeExtensions
	 * @param ExtensionsCollection<MethodParameterClosureTypeExtension> $methodParameterClosureTypeExtensions
	 * @param ExtensionsCollection<StaticMethodParameterClosureTypeExtension> $staticMethodParameterClosureTypeExtensions
	 * @param ExtensionsCollection<PerFileAnalysisResettable> $perFileAnalysisResettables
	 */
	public function __construct(
		private readonly Container $container,
		private readonly ReflectionProvider $reflectionProvider,
		#[AutowiredExtensions(of: FunctionParameterOutTypeExtension::class)]
		private readonly ExtensionsCollection $functionParameterOutTypeExtensions,
		#[AutowiredExtensions(of: MethodParameterOutTypeExtension::class)]
		private readonly ExtensionsCollection $methodParameterOutTypeExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterOutTypeExtension::class)]
		private readonly ExtensionsCollection $staticMethodParameterOutTypeExtensions,
		private readonly FileTypeMapper $fileTypeMapper,
		#[AutowiredExtensions(of: ReadWritePropertiesExtension::class)]
		private readonly ExtensionsCollection $readWritePropertiesExtensions,
		#[AutowiredExtensions(of: FunctionParameterClosureThisExtension::class)]
		private readonly ExtensionsCollection $functionParameterClosureThisExtensions,
		#[AutowiredExtensions(of: MethodParameterClosureThisExtension::class)]
		private readonly ExtensionsCollection $methodParameterClosureThisExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterClosureThisExtension::class)]
		private readonly ExtensionsCollection $staticMethodParameterClosureThisExtensions,
		#[AutowiredExtensions(of: FunctionParameterClosureTypeExtension::class)]
		private readonly ExtensionsCollection $functionParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: MethodParameterClosureTypeExtension::class)]
		private readonly ExtensionsCollection $methodParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterClosureTypeExtension::class)]
		private readonly ExtensionsCollection $staticMethodParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: PerFileAnalysisResettable::class)]
		private readonly ExtensionsCollection $perFileAnalysisResettables,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithLoopInitialAssignments,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithAlwaysIterableForeach,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private readonly bool $implicitThrows,
		#[AutowiredParameter]
		private readonly bool $treatPhpDocTypesAsCertain,
		private readonly ExpressionResultFactory $expressionResultFactory,
	)
	{
		self::$guardNewWorld = getenv('PHPSTAN_GUARD_NW') === '1';
	}

	/**
	 * @api
	 * @param string[] $files
	 */
	public function setAnalysedFiles(array $files): void
	{
		$this->analysedFiles = array_fill_keys($files, true);
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
		try {
			$this->processNodesWithStorage($nodes, $scope, $expressionResultStorage, $nodeCallback);
		} finally {
			$scope->popExpressionResultStorage();
		}
	}

	/**
	 * @param Node[] $nodes
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processNodesWithStorage(
		array $nodes,
		MutatingScope $scope,
		ExpressionResultStorage $expressionResultStorage,
		callable $nodeCallback,
	): void
	{
		$alreadyTerminated = false;
		$exitPoints = [];

		$stmts = [];
		$stmtToNodeIndex = [];
		foreach ($nodes as $i => $node) {
			if (!($node instanceof Node\Stmt)) {
				continue;
			}

			$stmtToNodeIndex[count($stmts)] = $i;
			$stmts[] = $node;
		}

		$dummyParent = new Node\Stmt\Nop();
		foreach ($stmts as $si => $node) {
			if ($alreadyTerminated && !($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\Label)) {
				continue;
			}

			$nestedLabelNames = $node->getAttribute(GotoLabelVisitor::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE);
			if ($nestedLabelNames !== null) {
				$scope = $this->resolveBackwardGotoScope(
					$dummyParent,
					[$node],
					$scope,
					$expressionResultStorage,
					StatementContext::createDeep(),
					static fn (string $name): bool => isset($nestedLabelNames[$name]),
					false,
				);
			}

			$statementResult = $this->processStmtNode($node, $scope, $expressionResultStorage, $nodeCallback, StatementContext::createTopLevel());
			$scope = $statementResult->getScope();

			if ($node instanceof Node\Stmt\Label) {
				$labelName = $node->name->toString();

				[$scope, $alreadyTerminated, $exitPoints] = $this->mergeForwardGotoExitPoints(
					$labelName,
					$scope,
					$alreadyTerminated,
					$exitPoints,
				);

				if ($alreadyTerminated) {
					continue;
				}

				if ($node->getAttribute(GotoLabelVisitor::HAS_BACKWARD_GOTO_ATTRIBUTE) === true) {
					$scope = $this->resolveBackwardGotoScope(
						$dummyParent,
						array_slice($stmts, $si + 1),
						$scope,
						$expressionResultStorage,
						StatementContext::createDeep(),
						static fn (string $name): bool => $name === $labelName,
						true,
					);
				}
			}

			$exitPoints = array_merge($exitPoints, $statementResult->getExitPoints());

			if ($alreadyTerminated || !$statementResult->isAlwaysTerminating()) {
				continue;
			}

			$alreadyTerminated = true;
			$nextStmts = $this->getNextUnreachableStatements(array_slice($nodes, $stmtToNodeIndex[$si] + 1), true);
			$this->processUnreachableStatement($nextStmts, $scope, $expressionResultStorage, $nodeCallback);
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
	 * @param Node\Stmt[] $bodyStmts
	 * @param Closure(string): bool $gotoNameMatcher
	 */
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
	 * @param Node\Stmt[] $bodyStmts
	 * @param Closure(string): bool $gotoNameMatcher
	 */
	private function resolveBackwardGotoScope(
		Node $parentNode,
		array $bodyStmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		StatementContext $context,
		Closure $gotoNameMatcher,
		bool $mergeBodyScopeEachIteration,
	): MutatingScope
	{
		$bodyScope = $scope;
		$count = 0;
		$prevEntryScope = null;
		do {
			$prevScope = $bodyScope;
			if ($mergeBodyScopeEachIteration) {
				$bodyScope = $bodyScope->mergeWith($scope);
			}
			if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
				// walking is deterministic in the entry scope - an unchanged entry
				// reproduces the previous pass's exit, so the verification walk is skipped
				$bodyScope = $prevScope;
				break;
			}
			$prevEntryScope = $bodyScope;
			$tempStorage = $storage->duplicate();
			$bodyScopeResult = $this->processStmtNodesInternal(
				$parentNode,
				$bodyStmts,
				$bodyScope,
				$tempStorage,
				new NoopNodeCallback(),
				$context,
			);

			$gotoScope = null;
			foreach ($bodyScopeResult->getExitPoints() as $ep) {
				$epStmt = $ep->getStatement();
				if (!($epStmt instanceof Goto_) || !$gotoNameMatcher($epStmt->name->toString())) {
					continue;
				}

				$gotoScope = $gotoScope === null ? $ep->getScope() : $gotoScope->mergeWith($ep->getScope());
			}

			if ($gotoScope !== null) {
				$bodyScope = $scope->mergeWith($gotoScope);
			}

			if ($bodyScope->equals($prevScope)) {
				break;
			}

			if ($count >= self::GENERALIZE_AFTER_ITERATION) {
				$bodyScope = $prevScope->generalizeWith($bodyScope);
			}
			$count++;
		} while ($count < self::LOOP_SCOPE_ITERATIONS);

		return $bodyScope;
	}

	/**
	 * @param InternalStatementExitPoint[] $exitPoints
	 * @return array{MutatingScope, bool, list<InternalStatementExitPoint>}
	 */
	private function mergeForwardGotoExitPoints(
		string $labelName,
		MutatingScope $scope,
		bool $alreadyTerminated,
		array $exitPoints,
	): array
	{
		$newExitPoints = [];
		foreach ($exitPoints as $exitPoint) {
			$exitStmt = $exitPoint->getStatement();
			if ($exitStmt instanceof Goto_ && $exitStmt->name->toString() === $labelName) {
				if ($alreadyTerminated) {
					$scope = $exitPoint->getScope();
					$alreadyTerminated = false;
				} else {
					$scope = $scope->mergeWith($exitPoint->getScope());
				}
			} else {
				$newExitPoints[] = $exitPoint;
			}
		}

		return [$scope, $alreadyTerminated, $newExitPoints];
	}

	/**
	 * @param Node\Stmt[] $nextStmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processUnreachableStatement(array $nextStmts, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback): void
	{
		if ($nextStmts === []) {
			return;
		}

		$unreachableStatement = null;
		$nextStatements = [];

		foreach ($nextStmts as $key => $nextStmt) {
			if ($key === 0) {
				$unreachableStatement = $nextStmt;
				continue;
			}

			$nextStatements[] = $nextStmt;
		}

		if (!$unreachableStatement instanceof Node\Stmt) {
			return;
		}

		$this->callNodeCallback($nodeCallback, new UnreachableStatementNode($unreachableStatement, $nextStatements), $scope, $storage);
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
			return $this->doProcessStmtNodes($parentNode, $stmts, $scope, $storage, $nodeCallback, $context);
		} finally {
			if ($pushStorage) {
				$scope->popExpressionResultStorage();
			}
		}
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function doProcessStmtNodes(
		Node $parentNode,
		array $stmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$exitPoints = [];
		$throwPoints = [];
		$impurePoints = [];
		$alreadyTerminated = false;
		$hasYield = false;
		$stmtCount = count($stmts);
		$shouldCheckLastStatement = $parentNode instanceof Node\Stmt\Function_
			|| $parentNode instanceof Node\Stmt\ClassMethod
			|| $parentNode instanceof PropertyHookStatementNode
			|| $parentNode instanceof Expr\Closure;

		foreach ($stmts as $i => $stmt) {
			if ($alreadyTerminated && !($stmt instanceof Node\Stmt\Function_ || $stmt instanceof Node\Stmt\ClassLike || $stmt instanceof Node\Stmt\Label)) {
				continue;
			}

			$isLast = $i === $stmtCount - 1;

			$nestedLabelNames = $stmt->getAttribute(GotoLabelVisitor::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE);
			if ($nestedLabelNames !== null && $context->isTopLevel()) {
				$scope = $this->resolveBackwardGotoScope(
					$parentNode,
					[$stmt],
					$scope,
					$storage,
					$context->enterDeep(),
					static fn (string $name): bool => isset($nestedLabelNames[$name]),
					false,
				);
			}

			$statementResult = $this->processStmtNode(
				$stmt,
				$scope,
				$storage,
				$nodeCallback,
				$context,
			);
			$scope = $statementResult->getScope();
			$hasYield = $hasYield || $statementResult->hasYield();

			if ($stmt instanceof Node\Stmt\Label) {
				$labelName = $stmt->name->toString();

				[$scope, $alreadyTerminated, $exitPoints] = $this->mergeForwardGotoExitPoints(
					$labelName,
					$scope,
					$alreadyTerminated,
					$exitPoints,
				);

				if ($alreadyTerminated) {
					continue;
				}

				if ($stmt->getAttribute(GotoLabelVisitor::HAS_BACKWARD_GOTO_ATTRIBUTE) === true && $context->isTopLevel()) {
					$scope = $this->resolveBackwardGotoScope(
						$parentNode,
						array_slice($stmts, $i + 1),
						$scope,
						$storage,
						$context->enterDeep(),
						static fn (string $name): bool => $name === $labelName,
						true,
					);
				}
			}

			if ($shouldCheckLastStatement && $isLast) {
				$endStatements = $statementResult->getEndStatements();
				if (count($endStatements) > 0) {
					foreach ($endStatements as $endStatement) {
						$endStatementResult = $endStatement->getResult();
						$this->callNodeCallback($nodeCallback, new ExecutionEndNode(
							$endStatement->getStatement(),
							(new InternalStatementResult(
								$endStatementResult->getScope(),
								$hasYield,
								$endStatementResult->isAlwaysTerminating(),
								$endStatementResult->getExitPoints(),
								$endStatementResult->getThrowPoints(),
								$endStatementResult->getImpurePoints(),
							))->toPublic(),
							$parentNode->getReturnType() !== null,
						), $endStatementResult->getScope(), $storage);
					}
				} else {
					$this->callNodeCallback($nodeCallback, new ExecutionEndNode(
						$stmt,
						(new InternalStatementResult(
							$scope,
							$hasYield,
							$statementResult->isAlwaysTerminating(),
							$statementResult->getExitPoints(),
							$statementResult->getThrowPoints(),
							$statementResult->getImpurePoints(),
						))->toPublic(),
						$parentNode->getReturnType() !== null,
					), $scope, $storage);
				}
			}

			$exitPoints = array_merge($exitPoints, $statementResult->getExitPoints());
			$throwPoints = array_merge($throwPoints, $statementResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $statementResult->getImpurePoints());

			if ($alreadyTerminated || !$statementResult->isAlwaysTerminating()) {
				continue;
			}

			$alreadyTerminated = true;
			$nextStmts = $this->getNextUnreachableStatements(array_slice($stmts, $i + 1), $parentNode instanceof Node\Stmt\Namespace_);
			$this->processUnreachableStatement($nextStmts, $scope, $storage, $nodeCallback);
		}

		$statementResult = new InternalStatementResult($scope, $hasYield, $alreadyTerminated, $exitPoints, $throwPoints, $impurePoints);
		if ($stmtCount === 0 && $shouldCheckLastStatement) {
			$returnTypeNode = $parentNode->getReturnType();
			if ($parentNode instanceof Expr\Closure) {
				$parentNode = new Node\Stmt\Expression($parentNode, $parentNode->getAttributes());
			}
			$this->callNodeCallback($nodeCallback, new ExecutionEndNode(
				$parentNode,
				$statementResult->toPublic(),
				$returnTypeNode !== null,
			), $scope, $storage);
		}

		return $statementResult;
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
				$scope = $this->processStmtVarAnnotation($scope, $storage, $stmt, null, $nodeCallback);
			}
			$overridingThrowPoints = $this->getOverridingThrowPoints($stmt, $scope);
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
			|| $stmt instanceof If_ || $stmt instanceof Switch_ || $stmt instanceof Foreach_;
		if (!$deferredStmtCallback) {
			$this->callNodeCallback($nodeCallback, $stmt, $scope, $storage);
		}

		$stmtHandler = StmtHandlerRegistry::resolve($stmt, $this->container);
		if ($stmtHandler !== null) {
			$stmtResult = $stmtHandler->processStmt($this, $stmt, $scope, $storage, $nodeCallback, $context);
			if ($overridingThrowPoints !== null) {
				return new InternalStatementResult(
					$stmtResult->getScope(),
					hasYield: $stmtResult->hasYield(),
					isAlwaysTerminating: $stmtResult->isAlwaysTerminating(),
					exitPoints: $stmtResult->getExitPoints(),
					throwPoints: $overridingThrowPoints,
					impurePoints: $stmtResult->getImpurePoints(),
					endStatements: $stmtResult->getEndStatements(),
				);
			}

			return $stmtResult;
		}

		// statements with no analysis of their own (e.g. HaltCompiler)
		return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: $overridingThrowPoints ?? [], impurePoints: []);
	}

	/**
	 * @return InternalThrowPoint[]|null
	 */
	private function getOverridingThrowPoints(Node\Stmt $statement, MutatingScope $scope): ?array
	{
		foreach ($statement->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}

			$function = $scope->getFunction();
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);

			$throwsTag = $resolvedPhpDoc->getThrowsTag();
			if ($throwsTag !== null) {
				$throwsType = $throwsTag->getType();
				if ($throwsType->isVoid()->yes()) {
					return [];
				}

				return [InternalThrowPoint::createExplicit($scope, $throwsType, $statement, false)];
			}
		}

		return null;
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
		// closure-argument consume guards in processArgs()
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
				ExpressionContext::createTopLevel(),
			);
		} finally {
			$scope->popExpressionResultStorage();
			$this->returnStoredExpressionResults = $previous;
		}
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

		if (
			!$expr instanceof Expr\Variable
			&& !$expr instanceof Expr\Closure
			&& !$expr instanceof Expr\ArrowFunction
			&& $scope->hasExpressionType($expr)->yes()
		) {
			return $scope->getTrackedExpressionType($expr);
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
			$this->storeExpressionResult($storage, $expr, $expressionResult);
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
				$this->callNodeCallbackWithExpression($nodeCallback, new FunctionCallExpressionNode($expr, $expressionResult), $scope, $storage, $context);
			} elseif ($expr instanceof MethodCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new MethodCallExpressionNode($expr, $expressionResult), $scope, $storage, $context);
			} elseif ($expr instanceof StaticCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new StaticMethodCallExpressionNode($expr, $expressionResult), $scope, $storage, $context);
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
		// Engine-feeding gatherers must observe the node at the emission
		// position - their arrays are read as soon as the enclosing body walk
		// returns. Gatherers are engine code and never ask about types -
		// handing them the raw scope skips a NodeCallbackScope construction per
		// emission; the scopes they capture (return statements, impure points)
		// answer later asks through the storage hub like any MutatingScope.
		while ($nodeCallback instanceof GatheringNodeCallback) {
			($nodeCallback->getGatherer())($node, $scope);
			$nodeCallback = $nodeCallback->getInner();
		}

		if ($nodeCallback instanceof NoopNodeCallback) {
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
		$callableParameters = $this->createCallableParameters($scope, $expr, $closureCallArgs, $passedToType);
		$nativeCallableParameters = $this->createNativeCallableParameters($scope, $expr, $closureCallArgs, $nativePassedToType);

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
					$inAssignRightSideType = $this->resolveCallableTypeForScope($inAssignRightSideExpr, $scope);
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
					$inAssignRightSideNativeType = $this->resolveCallableTypeForScope($inAssignRightSideExpr, $scope->doNotTreatPhpDocTypesAsCertain());
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
			$this->processExprNode($stmt, $use->var, $useScope, $storage, $nodeCallback, $context);
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
		$gatheredReturnStatementsWithScope = [];
		$gatheredYieldStatements = [];
		$gatheredYieldStatementsWithScope = [];
		$closureImpurePoints = [];
		$invalidateExpressions = [];
		$closureStmtsCallback = new GatheringNodeCallback(static function (Node $node, Scope $scope) use (&$executionEnds, &$gatheredReturnStatements, &$gatheredReturnStatementsWithScope, &$gatheredYieldStatements, &$gatheredYieldStatementsWithScope, &$closureScope, &$closureImpurePoints, &$invalidateExpressions): void {
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
		}, $nodeCallback);

		if (count($byRefUses) === 0) {
			$statementResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $closureStmtsCallback, StatementContext::createTopLevel());
			$publicStatementResult = $statementResult->toPublic();
			$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions);
			$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$publicStatementResult,
				$executionEnds,
				array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
			), $closureReturnStatementsNodeScope, $storage);

			return new ProcessClosureResult(
				$scope,
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
		do {
			$prevScope = $closureScope;

			$storage = $originalStorage->duplicate();
			// deep context, like the loop handlers' own convergence passes: inner
			// loops walk single-pass here and only the final walk below (top-level)
			// runs their full convergence - otherwise every closure-convergence
			// pass would re-converge every inner loop from scratch
			$intermediaryClosureScopeResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, new NoopNodeCallback(), StatementContext::createDeep());
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
		$statementResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $closureStmtsCallback, StatementContext::createTopLevel());
		$publicStatementResult = $statementResult->toPublic();
		$closureReturnStatementsNodeScope = $this->refineClosureNodeScope($closureScope, $scope, $expr, $gatheredReturnStatementsWithScope, $gatheredYieldStatementsWithScope, $executionEnds, $statementResult->getThrowPoints(), array_merge($closureImpurePoints, $statementResult->getImpurePoints()), $invalidateExpressions);
		$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredYieldStatements,
			$publicStatementResult,
			$executionEnds,
			array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
		), $closureReturnStatementsNodeScope, $storage);

		return new ProcessClosureResult(
			$scope,
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
	): ProcessArrowFunctionResult
	{
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}
		if ($expr->returnType !== null) {
			$this->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$arrowFunctionCallArgs = $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME);
		$callableParameters = $this->createCallableParameters($scope, $expr, $arrowFunctionCallArgs, $passedToType);
		$nativeCallableParameters = $this->createNativeCallableParameters($scope, $expr, $arrowFunctionCallArgs, $nativePassedToType);
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
		$arrowFunctionStmtsCallback = new GatheringNodeCallback(static function (Node $node, Scope $innerScope) use ($arrowFunctionScope, &$arrowFunctionImpurePoints, &$invalidateExpressions): void {
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
		}, $nodeCallback);

		$exprResult = $this->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $storage, $arrowFunctionStmtsCallback, ExpressionContext::createTopLevel());

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
	private function resolveCallableTypeForScope(Expr $expr, MutatingScope $scope): Type
	{
		if ($expr instanceof Expr\Closure || $expr instanceof Expr\ArrowFunction) {
			return $this->container->getByType(ClosureTypeResolver::class)->getClosureType($scope, $expr);
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
						$this->processArgs($stmt, $constructorReflection, null, $constructorReflection->getVariants(), $constructorReflection->getNamedArgumentsVariants(), $expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
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
	 * @param FunctionReflection|MethodReflection|null $calleeReflection
	 */
	private function resolveClosureThisType(
		?CallLike $call,
		$calleeReflection,
		ParameterReflection $parameter,
		MutatingScope $scope,
	): ?Type
	{
		if ($call instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterClosureThisExtensions->getAll() as $extension) {
				if (! $extension->isFunctionSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromFunctionCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		} elseif ($call instanceof StaticCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->staticMethodParameterClosureThisExtensions->getAll() as $extension) {
				if (! $extension->isStaticMethodSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromStaticMethodCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		} elseif ($call instanceof MethodCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->methodParameterClosureThisExtensions->getAll() as $extension) {
				if (! $extension->isMethodSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromMethodCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		}

		if ($parameter instanceof ExtendedParameterReflection) {
			return $parameter->getClosureThisType();
		}

		return null;
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @param (callable(MutatingScope): MutatingScope)|null $closureBindScopeFactory
	 */
	public function processArgs(
		Node\Stmt $stmt,
		$calleeReflection,
		?ExtendedMethodReflection $nakedMethodReflection,
		array $parametersAcceptors,
		?array $namedArgumentsVariants,
		CallLike $callLike,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?callable $closureBindScopeFactory = null,
	): ArgsResult
	{
		$args = $callLike->getArgs();

		// Evolving-scope arg types: gathered as each argument is processed on the
		// scope that evolves arg-to-arg. They select the FINAL resolved acceptor
		// (the call's return type, by-ref OUT types), which type-resolves generics
		// from the actual argument types.
		$gatheredTypes = [];
		$gatheredUnpack = false;
		$gatheredHasName = false;
		$gatheredArgTypeByIndex = [];

		// Metadata acceptor base - NO forward read. The per-argument resolution below picks the
		// count-correct variant (the by-ref/variadic STRUCTURE is variant-stable except where it is
		// keyed off the argument count, e.g. sscanf - and the count is known structurally) and
		// resolves generic parameter types from the args gathered so far; the call's return type
		// comes from the post-loop resolved acceptor.
		$metadataAcceptor = $parametersAcceptors[0] ?? null;

		// Whether selecting an acceptor is type-driven at all: multiple variants to
		// choose between, templates or conditionals to resolve from the arg types,
		// or named-argument variants. When it is not, the gathered arg types can
		// never influence the selected acceptor, so the faithful-return gather walk
		// of a closure/arrow argument (gatherClosureArgType()) would be pure waste -
		// its signature-only shallow type keeps the count/name bookkeeping correct.
		$typeDrivenAcceptorSelection = count($parametersAcceptors) > 1
			|| $namedArgumentsVariants !== null
			|| ($metadataAcceptor !== null && ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableType($metadataAcceptor));

		// Both predicates are hoisted out of the per-argument loop - they traverse
		// the acceptor's parameter types.
		$hasTemplateParameterType = $metadataAcceptor !== null
			&& ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType($metadataAcceptor);
		$argMetadataIsTypeDriven = count($parametersAcceptors) > 1 || $hasTemplateParameterType;

		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		/** @var list<array{InvalidateExprNode[], string[]}> $deferredInvalidateExpressions */
		$deferredInvalidateExpressions = [];
		/** @var ProcessClosureResult[] $deferredByRefClosureResults */
		$deferredByRefClosureResults = [];

		$processingOrder = array_keys($args);
		usort($processingOrder, static function (int $a, int $b) use ($args): int {
			$aOriginalArg = $args[$a]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$bOriginalArg = $args[$b]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$aValue = $aOriginalArg !== null ? $aOriginalArg->value : $args[$a]->value;
			$bValue = $bOriginalArg !== null ? $bOriginalArg->value : $args[$b]->value;
			$aIsClosure = $aValue instanceof Expr\Closure || $aValue instanceof Expr\ArrowFunction;
			$bIsClosure = $bValue instanceof Expr\Closure || $bValue instanceof Expr\ArrowFunction;
			if ($aIsClosure !== $bIsClosure) {
				// closures sort after non-closures so every sibling feeding an
				// intrinsic override / generic callable(T) is in scope first
				return $aIsClosure ? 1 : -1;
			}

			$aOriginal = $args[$a]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$bOriginal = $args[$b]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			if ($aOriginal === null && $bOriginal === null) {
				return $a <=> $b;
			}
			if ($aOriginal === null) {
				return 1;
			}
			if ($bOriginal === null) {
				return -1;
			}

			return $aOriginal->getStartTokenPos() <=> $bOriginal->getStartTokenPos();
		});

		$argResults = [];
		$countStableMetadataAcceptor = null;
		foreach ($processingOrder as $i) {
			$arg = $args[$i];

			if ($arg->value instanceof Expr\Closure || $arg->value instanceof Expr\ArrowFunction) {
				// Gather the closure/arrow type for the FINAL resolved acceptor on
				// the evolving scope, BEFORE the body is processed with a possibly
				// generic-resolved parameter injected, so the inferred return type
				// stays faithful to the closure's own declaration and its own
				// contribution (a TValue from its return) participates in the final
				// resolution (see gatherClosureArgType()).
				$originalArgForGather = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
				$gatheredArgTypeByIndex[$i] = $typeDrivenAcceptorSelection
					? $this->gatherClosureArgType($parametersAcceptors, $i, $arg->value, $scope)
					: $this->container->getByType(ClosureTypeResolver::class)->getClosureType($scope, $arg->value, true);
				$this->addGatheredArgType($gatheredTypes, $gatheredUnpack, $gatheredHasName, $originalArgForGather, $i, $gatheredArgTypeByIndex[$i]);
			}

			$argMetadataAcceptor = $metadataAcceptor;
			if ($metadataAcceptor !== null && $argMetadataIsTypeDriven) {
				if ($this->argConsumesResolvedParameterType($arg->value)) {
					// Resolve the acceptor for this argument from the args gathered SO FAR, padded to the
					// full argument count with mixed. Closures sort last and by-ref out-params follow the
					// args that pin them, so determining siblings are already processed; the mixed pad keeps
					// the argument COUNT correct so the by-ref/variadic variant stays stable (e.g. sscanf),
					// while processed siblings resolve a generic callable(T) parameter. No forward read.
					$paddedTypes = [];
					$paddedUnpack = false;
					$paddedHasName = false;
					foreach ($args as $j => $paddedArg) {
						$paddedOriginalArg = $paddedArg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $paddedArg;
						$this->addGatheredArgType($paddedTypes, $paddedUnpack, $paddedHasName, $paddedOriginalArg, $j, $gatheredArgTypeByIndex[$j] ?? new MixedType());
					}
					$argMetadataAcceptor = $this->selectArgsMetadataAcceptor($args, $paddedTypes, $parametersAcceptors, $namedArgumentsVariants, $paddedHasName, $paddedUnpack, $scope);
				} else {
					// Only a closure/arrow function consumes the generic-RESOLVED
					// parameter type: its body is inferred from the resolved
					// callable(T) - directly, or through the in-function-call stack
					// when nested anywhere inside the argument. Every other argument
					// reads variant-stable facts off its parameter (by-ref flag,
					// callable bookkeeping), so one all-mixed count-stable selection
					// serves them all instead of a full template inference per argument.
					if ($countStableMetadataAcceptor === null) {
						$paddedTypes = [];
						$paddedUnpack = false;
						$paddedHasName = false;
						foreach ($args as $j => $paddedArg) {
							$paddedOriginalArg = $paddedArg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $paddedArg;
							$this->addGatheredArgType($paddedTypes, $paddedUnpack, $paddedHasName, $paddedOriginalArg, $j, new MixedType());
						}
						$countStableMetadataAcceptor = $this->selectArgsMetadataAcceptor($args, $paddedTypes, $parametersAcceptors, $namedArgumentsVariants, $paddedHasName, $paddedUnpack, $scope);
					}
					$argMetadataAcceptor = $countStableMetadataAcceptor;
				}
			}
			$parameters = $argMetadataAcceptor !== null ? $argMetadataAcceptor->getParameters() : null;

			$assignByReference = false;
			$parameter = null;
			$parameterType = null;
			$parameterNativeType = null;
			if ($parameters !== null) {
				$matchedParameter = null;
				if ($arg->name !== null) {
					foreach ($parameters as $p) {
						if ($p->getName() === $arg->name->toString()) {
							$matchedParameter = $p;
							break;
						}
					}
				} elseif (isset($parameters[$i])) {
					$matchedParameter = $parameters[$i];
				}

				if ($matchedParameter !== null) {
					$assignByReference = $matchedParameter->passedByReference()->createsNewVariable();
					$parameterType = $matchedParameter->getType();

					if ($matchedParameter instanceof ExtendedParameterReflection) {
						$parameterNativeType = $matchedParameter->getNativeType();
					}
					$parameter = $matchedParameter;
				} elseif (count($parameters) > 0 && $argMetadataAcceptor->isVariadic()) {
					$lastParameter = array_last($parameters);
					$assignByReference = $lastParameter->passedByReference()->createsNewVariable();
					$parameterType = $lastParameter->getType();

					if ($lastParameter instanceof ExtendedParameterReflection) {
						$parameterNativeType = $lastParameter->getNativeType();
					}
					$parameter = $lastParameter;
				}
			}

			$lookForUnset = false;
			if ($assignByReference) {
				$isBuiltin = false;
				if ($calleeReflection instanceof FunctionReflection && $calleeReflection->isBuiltin()) {
					$isBuiltin = true;
				} elseif ($calleeReflection instanceof ExtendedMethodReflection && $calleeReflection->getDeclaringClass()->isBuiltin()) {
					$isBuiltin = true;
				}
				if (
					$isBuiltin
					|| ($parameterNativeType === null || !$parameterNativeType->isNull()->no())
				) {
					$scope = $this->lookForSetAllowedUndefinedExpressions($scope, $arg->value);
					$lookForUnset = true;
				}
			}

			$originalArg = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
			if ($calleeReflection !== null) {
				$rememberTypes = !$originalArg->value instanceof Expr\Closure && !$originalArg->value instanceof Expr\ArrowFunction;
				$scope = $scope->pushInFunctionCall($calleeReflection, $parameter, $rememberTypes);
			}

			$this->callNodeCallback($nodeCallback, $originalArg, $scope, $storage);

			$originalScope = $scope;
			$scopeToPass = $scope;
			if ($i === 0 && $closureBindScopeFactory !== null && ($arg->value instanceof Expr\Closure || $arg->value instanceof Expr\ArrowFunction)) {
				$scopeToPass = $closureBindScopeFactory($scope);
			}

			if ($arg->value instanceof Expr\Closure) {

				$storedClosureArgResult = null;
				if ($this->returnStoredExpressionResults || $this->consumeStoredExpressionResults) {
					// an on-demand re-walk of the enclosing call must not re-run the
					// closure's whole by-ref convergence: consume the main walk's
					// stored result, or (when the body release already dropped it)
					// price the closure through getClosureType's per-node cache -
					// a single body walk on miss, none on repeat asks
					$storedClosureArgResult = $storage->findExpressionResult($arg->value);
					if ($storedClosureArgResult === null) {
						$closureTypeResolver = $this->container->getByType(ClosureTypeResolver::class);
						$storedClosureArgResult = $this->expressionResultFactory->create(
							$scopeToPass,
							beforeScope: $scopeToPass,
							expr: $arg->value,
							hasYield: false,
							isAlwaysTerminating: false,
							throwPoints: [],
							impurePoints: [],
							type: $closureTypeResolver->getClosureType($scopeToPass, $arg->value),
							nativeType: $closureTypeResolver->getClosureType($scopeToPass->doNotTreatPhpDocTypesAsCertain(), $arg->value),
							typeCallback: null,
							specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
						);
						$this->storeExpressionResult($storage, $arg->value, $storedClosureArgResult);
					}
				}
				if ($storedClosureArgResult !== null) {
					$argResults[spl_object_id($arg->value)] = $storedClosureArgResult;
				} else {
					$restoreThisScope = null;
					if (
						$closureBindScopeFactory === null
						&& $parameter instanceof ExtendedParameterReflection
						&& !$arg->value->static
					) {
						$closureThisType = $this->resolveClosureThisType($callLike, $calleeReflection, $parameter, $scopeToPass);
						if ($closureThisType !== null) {
							$restoreThisScope = $scopeToPass;
							$scopeToPass = $scopeToPass->assignVariable('this', $closureThisType, new ObjectWithoutClassType(), TrinaryLogic::createYes())
								->withClosureBindScopeClasses($closureThisType->getObjectClassNames());
						}
					}

					if ($parameter !== null) {
						$overwritingParameterType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass);

						if ($overwritingParameterType !== null) {
							$parameterType = $overwritingParameterType;

							// resolve the native flavour through the same extension on the
							// natively-promoted scope, so the closure parameters keep
							// their native precision too
							$overwritingParameterNativeType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass->doNotTreatPhpDocTypesAsCertain());
							if ($overwritingParameterNativeType !== null) {
								$parameterNativeType = $overwritingParameterNativeType;
							}
						}
					}

					$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
					$closureResult = $this->processClosureNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context, $parameterType, $parameterNativeType);
					if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
						$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $closureResult->getThrowPoints()));
						$impurePoints = array_merge($impurePoints, $closureResult->getImpurePoints());
					}

					$closureTypeResolver = $this->container->getByType(ClosureTypeResolver::class);
					$this->storeExpressionResult($storage, $arg->value, $this->expressionResultFactory->create(
						$closureResult->getScope(),
						$scopeToPass,
						$arg->value,
						hasYield: false,
						isAlwaysTerminating: false,
						throwPoints: [],
						impurePoints: [],
						type: $closureTypeResolver->buildClosureTypeForClosure(
							$scopeToPass,
							$arg->value,
							$closureResult->getGatheredReturnStatements(),
							$closureResult->getGatheredYieldStatements(),
							$closureResult->getExecutionEnds(),
							$closureResult->getThrowPoints(),
							$closureResult->getClosureTypeImpurePoints(),
							$closureResult->getInvalidateExpressions(),
						),
						// the native flavour reads the stored native types off the same
						// single body walk - no second walk on the promoted scope
						nativeType: $closureTypeResolver->buildClosureTypeForClosure(
							$scopeToPass,
							$arg->value,
							$closureResult->getGatheredReturnStatements(),
							$closureResult->getGatheredYieldStatements(),
							$closureResult->getExecutionEnds(),
							$closureResult->getThrowPoints(),
							$closureResult->getClosureTypeImpurePoints(),
							$closureResult->getInvalidateExpressions(),
							true,
						),
						typeCallback: null,
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					));

					$uses = [];
					foreach ($arg->value->uses as $use) {
						if (!is_string($use->var->name)) {
							continue;
						}

						$uses[] = $use->var->name;
					}

					$scope = $closureResult->getScope();
					$deferredByRefClosureResults[] = $closureResult;
					// Prefer the invalidate expressions collected on the ClosureType -
					// they also cover writes the closure's own body walk observed,
					// unlike $closureResult->getInvalidateExpressions().
					$closureExprType = $scope->getType($arg->value);
					$invalidateExpressions = $closureExprType instanceof ClosureType
						? $closureExprType->getInvalidateExpressions()
						: $closureResult->getInvalidateExpressions();
					if ($restoreThisScope !== null) {
						$nodeFinder = new NodeFinder();
						$cb = static fn ($expr) => $expr instanceof Variable && $expr->name === 'this';
						foreach ($invalidateExpressions as $j => $invalidateExprNode) {
							$foundThis = $nodeFinder->findFirst([$invalidateExprNode->getExpr()], $cb);
							if ($foundThis === null) {
								continue;
							}

							unset($invalidateExpressions[$j]);
						}
						$invalidateExpressions = array_values($invalidateExpressions);
						$scope = $scope->restoreThis($restoreThisScope);
					}

					if ($this->shouldInvalidateCallbackExpressions($parameter)) {
						$deferredInvalidateExpressions[] = [$invalidateExpressions, $uses];
					}
				}
			} elseif ($arg->value instanceof Expr\ArrowFunction) {

				$storedClosureArgResult = null;
				if ($this->returnStoredExpressionResults || $this->consumeStoredExpressionResults) {
					// see the Closure branch above - consume or price via the cache
					$storedClosureArgResult = $storage->findExpressionResult($arg->value);
					if ($storedClosureArgResult === null) {
						$closureTypeResolver = $this->container->getByType(ClosureTypeResolver::class);
						$storedClosureArgResult = $this->expressionResultFactory->create(
							$scopeToPass,
							beforeScope: $scopeToPass,
							expr: $arg->value,
							hasYield: false,
							isAlwaysTerminating: false,
							throwPoints: [],
							impurePoints: [],
							type: $closureTypeResolver->getClosureType($scopeToPass, $arg->value),
							nativeType: $closureTypeResolver->getClosureType($scopeToPass->doNotTreatPhpDocTypesAsCertain(), $arg->value),
							typeCallback: null,
							specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
						);
						$this->storeExpressionResult($storage, $arg->value, $storedClosureArgResult);
					}
				}
				if ($storedClosureArgResult !== null) {
					$argResults[spl_object_id($arg->value)] = $storedClosureArgResult;
				} else {
					if (
						$closureBindScopeFactory === null
						&& $parameter instanceof ExtendedParameterReflection
						&& !$arg->value->static
					) {
						$closureThisType = $this->resolveClosureThisType($callLike, $calleeReflection, $parameter, $scopeToPass);
						if ($closureThisType !== null) {
							$scopeToPass = $scopeToPass->assignVariable('this', $closureThisType, new ObjectWithoutClassType(), TrinaryLogic::createYes())
								->withClosureBindScopeClasses($closureThisType->getObjectClassNames());
						}
					}

					if ($parameter !== null) {
						$overwritingParameterType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass);

						if ($overwritingParameterType !== null) {
							$parameterType = $overwritingParameterType;

							// resolve the native flavour through the same extension on the
							// natively-promoted scope, so the closure parameters keep
							// their native precision too
							$overwritingParameterNativeType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass->doNotTreatPhpDocTypesAsCertain());
							if ($overwritingParameterNativeType !== null) {
								$parameterNativeType = $overwritingParameterNativeType;
							}
						}
					}

					$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
					$arrowFunctionResult = $this->processArrowFunctionNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $parameterType, $parameterNativeType);
					$arrowFunctionExprResult = $arrowFunctionResult->getExpressionResult();
					if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
						$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $arrowFunctionExprResult->getThrowPoints()));
						$impurePoints = array_merge($impurePoints, $arrowFunctionExprResult->getImpurePoints());
					}
					$arrowFunctionClosureTypeResolver = $this->container->getByType(ClosureTypeResolver::class);
					$arrowFunctionScope = $arrowFunctionResult->getArrowFunctionScope();
					// both flavours are built from the single body walk (see
					// ArrowFunctionHandler); the built type also answers the
					// invalidate-expressions read below without re-walking the
					// still-unstored node through Scope::getType()
					$arrowFunctionType = $arrowFunctionClosureTypeResolver->buildClosureTypeForArrowFunction(
						$scopeToPass,
						$arg->value,
						$arrowFunctionScope,
						$arrowFunctionResult->getClosureTypeThrowPoints(),
						$arrowFunctionResult->getClosureTypeImpurePoints(),
						$arrowFunctionResult->getInvalidateExpressions(),
					);
					$storedArrowResult = $this->expressionResultFactory->create(
						$arrowFunctionExprResult->getScope(),
						beforeScope: $scopeToPass,
						expr: $arg->value,
						hasYield: $arrowFunctionExprResult->hasYield(),
						isAlwaysTerminating: $arrowFunctionExprResult->isAlwaysTerminating(),
						throwPoints: $arrowFunctionExprResult->getThrowPoints(),
						impurePoints: $arrowFunctionExprResult->getImpurePoints(),
						type: $arrowFunctionType,
						nativeType: $arrowFunctionClosureTypeResolver->buildClosureTypeForArrowFunction(
							$scopeToPass,
							$arg->value,
							$arrowFunctionScope,
							$arrowFunctionResult->getClosureTypeThrowPoints(),
							$arrowFunctionResult->getClosureTypeImpurePoints(),
							$arrowFunctionResult->getInvalidateExpressions(),
							true,
						),
						typeCallback: null,
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					);
					$this->storeExpressionResult($storage, $arg->value, $storedArrowResult);
					// the arg result must be the properly-typed stored result, not
					// the body walk's placeholder (whose typeCallback answers mixed) -
					// ArgsResult readers price array_push() & co. from it
					$argResults[spl_object_id($arg->value)] = $storedArrowResult;
					if ($this->shouldInvalidateCallbackExpressions($parameter)) {
						$deferredInvalidateExpressions[] = [$arrowFunctionType->getInvalidateExpressions(), $arrowFunctionType->getUsedVariables()];
					}
				}
			} else {
				$enterExpressionAssignForByRef = $assignByReference && $arg->value instanceof ArrayDimFetch && $arg->value->dim === null;
				if ($enterExpressionAssignForByRef) {
					$scopeToPass = $scopeToPass->enterExpressionAssign($arg->value);
				}
				$exprResult = $this->processExprNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context->enterDeep());
				$argResults[spl_object_id($arg->value)] = $exprResult;
				$exprType = $exprResult->getType();
				$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $exprResult->isAlwaysTerminating();
				$scope = $exprResult->getScope();
				if ($enterExpressionAssignForByRef) {
					$scope = $scope->exitExpressionAssign($arg->value);
				}
				$hasYield = $hasYield || $exprResult->hasYield();

				if ($exprType->isCallable()->yes()) {
					$acceptors = $exprType->getCallableParametersAcceptors($scope);
					if (count($acceptors) === 1) {
						if ($this->shouldInvalidateCallbackExpressions($parameter)) {
							$deferredInvalidateExpressions[] = [$acceptors[0]->getInvalidateExpressions(), $acceptors[0]->getUsedVariables()];
						}
						if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
							$callableThrowPoints = array_map(static fn (SimpleThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $acceptors[0]->getThrowPoints());
							if (!$this->implicitThrows) {
								$callableThrowPoints = array_values(array_filter($callableThrowPoints, static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit()));
							}
							$throwPoints = array_merge($throwPoints, $callableThrowPoints);
							$impurePoints = array_merge($impurePoints, array_map(static fn (SimpleImpurePoint $impurePoint) => new ImpurePoint($scope, $arg->value, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $acceptors[0]->getImpurePoints()));
						}
					}
				}

				$gatheredArgTypeByIndex[$i] = $exprResult->getType();
				$this->addGatheredArgType($gatheredTypes, $gatheredUnpack, $gatheredHasName, $originalArg, $i, $gatheredArgTypeByIndex[$i]);
			}

			if ($assignByReference && $lookForUnset) {
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $arg->value);
			}

			if ($calleeReflection !== null) {
				$scope = $scope->popInFunctionCall();
			}

			if ($i !== 0 || $closureBindScopeFactory === null) {
				continue;
			}

			$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
		}

		foreach ($deferredInvalidateExpressions as [$invalidateExpressions, $uses]) {
			$scope = $this->processImmediatelyCalledCallable($scope, $invalidateExpressions, $uses);
		}

		foreach ($deferredByRefClosureResults as $deferredClosureResult) {
			$scope = $deferredClosureResult->applyByRefUseScope($scope);
		}

		// Type-driven resolved acceptor: the arg types gathered on the evolving
		// scope select (and generic-resolve) the acceptor that drives the call's
		// return type. Intrinsic overrides are applied on the final scope,
		// mirroring the original selectFromArgs().
		// When the selection is not type-driven, the single acceptor IS the
		// resolved acceptor - the fast path selectFromArgs() used to take.
		$resolvedAcceptor = null;
		if ($parametersAcceptors !== []) {
			$resolvedAcceptor = $typeDrivenAcceptorSelection
				? $this->selectArgsMetadataAcceptor($args, $gatheredTypes, $parametersAcceptors, $namedArgumentsVariants, $gatheredHasName, $gatheredUnpack, $scope)
				: $metadataAcceptor;
		}

		// The by-ref OUT writeback reads the metadata acceptor: it is selected from
		// the full argument count (stable variant). When that single acceptor still
		// carries templates (fast path), its OUT types need generic-resolving from the
		// now-complete gathered arg types - the post-loop $resolvedAcceptor is exactly
		// that (same variant, resolved); otherwise the metadata acceptor is already resolved.
		$writebackAcceptor = $metadataAcceptor;
		if (
			$metadataAcceptor !== null
			&& $argMetadataIsTypeDriven
		) {
			$writebackAcceptor = $resolvedAcceptor;
		}
		$writebackParameters = $writebackAcceptor !== null ? $writebackAcceptor->getParameters() : null;
		if ($writebackParameters !== null) {
			foreach ($args as $i => $arg) {
				$assignByReference = false;
				$currentParameter = null;
				if (isset($writebackParameters[$i])) {
					$currentParameter = $writebackParameters[$i];
				} elseif (count($writebackParameters) > 0 && $writebackAcceptor->isVariadic()) {
					$currentParameter = array_last($writebackParameters);
				}

				if ($currentParameter !== null) {
					$assignByReference = $currentParameter->passedByReference()->createsNewVariable();
				}

				if ($assignByReference) {
					if ($currentParameter === null) {
						throw new ShouldNotHappenException();
					}

					$argValue = $arg->value;
					if (!$argValue instanceof Variable || $argValue->name !== 'this') {
						$paramOutType = $this->getParameterOutExtensionsType($callLike, $calleeReflection, $currentParameter, $scope);
						if ($paramOutType !== null) {
							$byRefType = $paramOutType;
						} elseif (
							$currentParameter instanceof ExtendedParameterReflection
							&& $currentParameter->getOutType() !== null
						) {
							$byRefType = $currentParameter->getOutType();
						} elseif (
							$calleeReflection instanceof MethodReflection
							&& !$calleeReflection->getDeclaringClass()->isBuiltin()
						) {
							$byRefType = $currentParameter->getType();
						} elseif (
							$calleeReflection instanceof FunctionReflection
							&& !$calleeReflection->isBuiltin()
						) {
							$byRefType = $currentParameter->getType();
						} else {
							$byRefType = new MixedType();
						}

						$scope = $this->processVirtualAssign(
							$scope,
							$storage,
							$stmt,
							$argValue,
							new TypeExpr($byRefType),
							$nodeCallback,
						)->getScope();
						$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $argValue);
					}
				} elseif ($calleeReflection !== null && $calleeReflection->hasSideEffects()->yes()) {
					$argType = ($argResults[spl_object_id($arg->value)] ?? $this->readStoredResult($arg->value, $storage))->getTypeOnScope($scope, false);
					if (!$argType->isObject()->no()) {
						$nakedReturnType = null;
						if ($nakedMethodReflection !== null) {
							$nakedParametersAcceptor = $this->selectArgsAcceptor(
								$gatheredTypes,
								$nakedMethodReflection->getVariants(),
								$nakedMethodReflection->getNamedArgumentsVariants(),
								$gatheredHasName,
								$gatheredUnpack,
							);
							$nakedReturnType = $nakedParametersAcceptor->getReturnType();
						}
						if (
							$nakedReturnType === null
							|| !(new ThisType($nakedMethodReflection->getDeclaringClass()))->isSuperTypeOf($nakedReturnType)->yes()
							|| $nakedMethodReflection->isPure()->no()
						) {
							$this->callNodeCallback($nodeCallback, new InvalidateExprNode($arg->value), $scope, $storage);
							$scope = $scope->invalidateExpression($arg->value, true);
						}
					} elseif (!(new ResourceType())->isSuperTypeOf($argType)->no()) {
						$this->callNodeCallback($nodeCallback, new InvalidateExprNode($arg->value), $scope, $storage);
						$scope = $scope->invalidateExpression($arg->value, true);
					}
				}
			}
		}

		// not storing this, it's scope after processing all args
		return new ArgsResult(
			$this->expressionResultFactory->create(
				$scope,
				$scope,
				$callLike,
				$hasYield,
				$isAlwaysTerminating,
				$throwPoints,
				$impurePoints,
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			),
			$resolvedAcceptor,
			$argResults,
		);
	}

	/**
	 * Ports the gather-keying of ParametersAcceptorSelector::selectFromArgs():
	 * indexes the gathered arg type by name (sets $hasName) vs position, and
	 * expands unpacked constant arrays / falls back to the iterable value type
	 * (sets $unpack), so selectFromTypes() picks the matching variant.
	 *
	 * @param array<int|string, Type> $types
	 */
	private function addGatheredArgType(array &$types, bool &$unpack, bool &$hasName, Node\Arg $originalArg, int $i, Type $type): void
	{
		if ($originalArg->name !== null) {
			$index = $originalArg->name->toString();
			$hasName = true;
		} else {
			$index = $i;
		}

		if ($originalArg->unpack) {
			$unpack = true;
			$constantArrays = $type->getConstantArrays();
			if (count($constantArrays) > 0) {
				foreach ($constantArrays as $constantArray) {
					$values = $constantArray->getValueTypes();
					foreach ($constantArray->getKeyTypes() as $j => $keyType) {
						$valueType = $values[$j];
						$valueIndex = $keyType->getValue();
						if (is_string($valueIndex)) {
							$hasName = true;
						} else {
							$valueIndex = $i + $j;
						}

						$types[$valueIndex] = isset($types[$valueIndex])
							? TypeCombinator::union($types[$valueIndex], $valueType)
							: $valueType;
					}
				}
			} else {
				$types[$index] = $type->getIterableValueType();
			}
		} else {
			$types[$index] = $type;
		}
	}

	/**
	 * Whether processing this argument consumes the generic-RESOLVED parameter
	 * type: a closure/arrow function does - its parameters and body scope are
	 * typed from the resolved callable(T) - whether it IS the argument or is
	 * nested anywhere inside it (the enclosing parameter is pushed on the
	 * in-function-call stack and the nested closure types itself from there).
	 * Every other argument only reads variant-stable facts off its parameter.
	 */
	private function argConsumesResolvedParameterType(Expr $value): bool
	{
		if ($value instanceof Expr\Closure || $value instanceof Expr\ArrowFunction) {
			return true;
		}

		// cached on the node - args are re-processed across convergence passes
		$cached = $value->getAttribute('phpstanArgContainsClosure');
		if ($cached !== null) {
			return $cached;
		}

		$contains = (new NodeFinder())->findFirst(
			[$value],
			static fn (Node $node): bool => $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction,
		) !== null;
		$value->setAttribute('phpstanArgContainsClosure', $contains);

		return $contains;
	}

	/**
	 * Resolves the type of a closure/arrow function argument for the generic
	 * gather, mirroring ParametersAcceptorSelector::selectFromArgs(): the closure
	 * type is read with the RAW (un-generic-resolved) acceptor parameter pushed
	 * onto the in-function-call stack, so its body sees the template parameter
	 * (effectively mixed for an untyped param) rather than a parameter already
	 * resolved from sibling args. That keeps the inferred return type (the U in
	 * callable(T): U) faithful to the closure's own declaration.
	 *
	 * @param ParametersAcceptor[] $parametersAcceptors
	 */
	private function gatherClosureArgType(array $parametersAcceptors, int $i, Expr $closureExpr, MutatingScope $scope): Type
	{
		$rawParameter = null;
		if (count($parametersAcceptors) === 1) {
			$rawParameters = $parametersAcceptors[0]->getParameters();
			if (isset($rawParameters[$i])) {
				$rawParameter = $rawParameters[$i];
			} elseif (count($rawParameters) > 0 && $parametersAcceptors[0]->isVariadic()) {
				$rawParameter = array_last($rawParameters);
			}
		}

		if ($rawParameter !== null) {
			$scope = $scope->pushInFunctionCall(null, $rawParameter, false);
		}

		return $this->resolveCallableTypeForScope($closureExpr, $scope);
	}

	/**
	 * @param array<int|string, Type> $types
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 */
	private function selectArgsAcceptor(array $types, array $parametersAcceptors, ?array $namedArgumentsVariants, bool $hasName, bool $unpack): ParametersAcceptor
	{
		return $hasName && $namedArgumentsVariants !== null
			? ParametersAcceptorSelector::selectFromTypes($types, $namedArgumentsVariants, $unpack)
			: ParametersAcceptorSelector::selectFromTypes($types, $parametersAcceptors, $unpack);
	}

	/**
	 * Applies the intrinsic argument overrides (array_map/filter/walk/find,
	 * curl_setopt, implode, Closure::bind) on the arg-to-arg evolved scope via
	 * the non-reprocessing readers, then type-selects the metadata acceptor over
	 * the arg types gathered so far. The overrides read sibling arg types - which
	 * closures-last ordering keeps in scope/$gatheredTypes before any closure.
	 *
	 * @param Node\Arg[] $args
	 * @param array<int|string, Type> $gatheredTypes
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 */
	private function selectArgsMetadataAcceptor(array $args, array $gatheredTypes, array $parametersAcceptors, ?array $namedArgumentsVariants, bool $hasName, bool $unpack, MutatingScope $scope): ParametersAcceptor
	{
		$overridden = ParametersAcceptorSelector::applyIntrinsicArgOverrides(
			$args,
			$parametersAcceptors,
			$namedArgumentsVariants,
			$scope,
			fn (Expr $e): Type => $this->readTypeOfMaybeStored($e, $scope),
			fn (Expr $e): Type => $this->readTypeOfMaybeStored($e, $scope->doNotTreatPhpDocTypesAsCertain()),
			static fn (Type $t): Type => $scope->getIterableValueType($t),
			static fn (Type $t): Type => $scope->getIterableKeyType($t),
		);

		return $this->selectArgsAcceptor($gatheredTypes, $overridden, $namedArgumentsVariants, $hasName, $unpack);
	}

	/**
	 * Arguments normalization (reordering, default-filling) can drop an original
	 * argument from the call processArgs() iterates - duplicate, unknown-named or
	 * extra arguments in an invalid call. The parameters check still asks their
	 * types to report the error, so process them too (their result is stored).
	 * A NoopNodeCallback keeps the dropped arguments out of rule processing,
	 * matching the behaviour when this guard is off.
	 */
	public function processDroppedArgs(
		Node\Stmt $stmt,
		CallLike $originalCall,
		CallLike $normalizedCall,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		ExpressionContext $context,
	): void
	{
		if ($originalCall === $normalizedCall) {
			return;
		}

		$keptValueIds = [];
		foreach ($normalizedCall->getArgs() as $normalizedArg) {
			$keptValueIds[spl_object_id($normalizedArg->value)] = true;
		}

		foreach ($originalCall->getArgs() as $originalArg) {
			if (isset($keptValueIds[spl_object_id($originalArg->value)])) {
				continue;
			}

			$this->processExprNode($stmt, $originalArg->value, $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
		}
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function callCallbackImmediately(?ParameterReflection $parameter, ?Type $parameterType, $calleeReflection): bool
	{
		$parameterCallableType = null;
		if ($parameterType !== null && $calleeReflection instanceof FunctionReflection) {
			$parameterCallableType = TypeUtils::findCallableType($parameterType);
		}

		if ($parameter instanceof ExtendedParameterReflection) {
			$parameterCallImmediately = $parameter->isImmediatelyInvokedCallable();
			if ($parameterCallImmediately->maybe()) {
				$callCallbackImmediately = $parameterCallableType !== null;
			} else {
				$callCallbackImmediately = $parameterCallImmediately->yes();
			}
		} else {
			$callCallbackImmediately = $parameterCallableType !== null;
		}

		return $callCallbackImmediately;
	}

	/**
	 * A callback passed as an argument escapes the current scope and may be invoked,
	 * so its mutations have to invalidate the outer scope - unless the parameter is
	 * explicitly marked as later-invoked, in which case the callback only runs after
	 * the current function returns and its mutations are not visible here yet.
	 */
	private function shouldInvalidateCallbackExpressions(?ParameterReflection $parameter): bool
	{
		if ($parameter instanceof ExtendedParameterReflection) {
			return !$parameter->isImmediatelyInvokedCallable()->no();
		}

		return true;
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function getParameterTypeFromParameterClosureTypeExtension(CallLike $callLike, $calleeReflection, ParameterReflection $parameter, MutatingScope $scope): ?Type
	{
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterClosureTypeExtensions->getAll() as $functionParameterClosureTypeExtension) {
				if ($functionParameterClosureTypeExtension->isFunctionSupported($calleeReflection, $parameter)) {
					return $functionParameterClosureTypeExtension->getTypeFromFunctionCall($calleeReflection, $callLike, $parameter, $scope);
				}
			}
		} elseif ($calleeReflection instanceof MethodReflection) {
			if ($callLike instanceof StaticCall) {
				foreach ($this->staticMethodParameterClosureTypeExtensions->getAll() as $staticMethodParameterClosureTypeExtension) {
					if ($staticMethodParameterClosureTypeExtension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $staticMethodParameterClosureTypeExtension->getTypeFromStaticMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof New_ && $callLike->class instanceof Name) {
				$staticCall = new StaticCall(
					$callLike->class,
					new Identifier('__construct'),
					$callLike->getArgs(),
				);
				foreach ($this->staticMethodParameterClosureTypeExtensions->getAll() as $staticMethodParameterClosureTypeExtension) {
					if ($staticMethodParameterClosureTypeExtension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $staticMethodParameterClosureTypeExtension->getTypeFromStaticMethodCall($calleeReflection, $staticCall, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof MethodCall) {
				foreach ($this->methodParameterClosureTypeExtensions->getAll() as $methodParameterClosureTypeExtension) {
					if ($methodParameterClosureTypeExtension->isMethodSupported($calleeReflection, $parameter)) {
						return $methodParameterClosureTypeExtension->getTypeFromMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function getParameterOutExtensionsType(CallLike $callLike, $calleeReflection, ParameterReflection $currentParameter, MutatingScope $scope): ?Type
	{
		$paramOutTypes = [];
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterOutTypeExtensions->getAll() as $functionParameterOutTypeExtension) {
				if (!$functionParameterOutTypeExtension->isFunctionSupported($calleeReflection, $currentParameter)) {
					continue;
				}

				$resolvedType = $functionParameterOutTypeExtension->getParameterOutTypeFromFunctionCall($calleeReflection, $callLike, $currentParameter, $scope);
				if ($resolvedType === null) {
					continue;
				}
				$paramOutTypes[] = $resolvedType;
			}
		} elseif ($callLike instanceof MethodCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->methodParameterOutTypeExtensions->getAll() as $methodParameterOutTypeExtension) {
				if (!$methodParameterOutTypeExtension->isMethodSupported($calleeReflection, $currentParameter)) {
					continue;
				}

				$resolvedType = $methodParameterOutTypeExtension->getParameterOutTypeFromMethodCall($calleeReflection, $callLike, $currentParameter, $scope);
				if ($resolvedType === null) {
					continue;
				}
				$paramOutTypes[] = $resolvedType;
			}
		} elseif ($callLike instanceof StaticCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->staticMethodParameterOutTypeExtensions->getAll() as $staticMethodParameterOutTypeExtension) {
				if (!$staticMethodParameterOutTypeExtension->isStaticMethodSupported($calleeReflection, $currentParameter)) {
					continue;
				}

				$resolvedType = $staticMethodParameterOutTypeExtension->getParameterOutTypeFromStaticMethodCall($calleeReflection, $callLike, $currentParameter, $scope);
				if ($resolvedType === null) {
					continue;
				}
				$paramOutTypes[] = $resolvedType;
			}
		}

		if (count($paramOutTypes) === 1) {
			return $paramOutTypes[0];
		}

		if (count($paramOutTypes) > 1) {
			return TypeCombinator::union(...$paramOutTypes);
		}

		return null;
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
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processStmtVarAnnotation(MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, ?Expr $defaultExpr, callable $nodeCallback): MutatingScope
	{
		$function = $scope->getFunction();
		$variableLessTags = [];

		foreach ($stmt->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}

			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);

			$assignedVariable = null;
			if (
				$stmt instanceof Node\Stmt\Expression
				&& ($stmt->expr instanceof Assign || $stmt->expr instanceof AssignRef)
				&& $stmt->expr->var instanceof Variable
				&& is_string($stmt->expr->var->name)
			) {
				$assignedVariable = $stmt->expr->var->name;
			}

			foreach ($resolvedPhpDoc->getVarTags() as $name => $varTag) {
				if (is_int($name)) {
					$variableLessTags[] = $varTag;
					continue;
				}

				if ($name === $assignedVariable) {
					continue;
				}

				$certainty = $scope->hasVariableType($name);
				if ($certainty->no()) {
					continue;
				}

				if ($scope->isInClass() && $scope->getFunction() === null) {
					continue;
				}

				if ($scope->canAnyVariableExist()) {
					$certainty = TrinaryLogic::createYes();
				}

				$variableNode = new Variable($name, $stmt->getAttributes());
				$originalType = $scope->getVariableType($name);
				if (!$originalType->equals($varTag->getType())) {
					$this->callNodeCallback($nodeCallback, new VarTagChangedExpressionTypeNode($varTag, $variableNode), $scope, $storage);
				}

				$nativeScope = $scope->doNotTreatPhpDocTypesAsCertain();
				$scope = $scope->assignVariable(
					$name,
					$varTag->getType(),
					// a plain variable read is scope state
					$nativeScope->hasVariableType($name)->no() ? new ErrorType() : $nativeScope->getVariableType($name),
					$certainty,
				);
			}
		}

		if (count($variableLessTags) === 1 && $defaultExpr !== null) {
			$originalType = $this->readTypeOfMaybeStored($defaultExpr, $scope);
			$varTag = $variableLessTags[0];
			if (!$originalType->equals($varTag->getType())) {
				$this->callNodeCallback($nodeCallback, new VarTagChangedExpressionTypeNode($varTag, $defaultExpr), $scope, $storage);
			}
			$scope = $scope->assignExpression($defaultExpr, $varTag->getType(), new MixedType());
		}

		return $scope;
	}

	/**
	 * @param array<Node> $nodes
	 * @return list<Node\Stmt>
	 */
	private function getNextUnreachableStatements(array $nodes, bool $earlyBinding): array
	{
		$stmts = [];
		$isPassedUnreachableStatement = false;
		foreach ($nodes as $node) {
			if ($node instanceof Node\Stmt\Label) {
				break;
			}
			if ($earlyBinding && ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\HaltCompiler)) {
				continue;
			}
			if ($isPassedUnreachableStatement && $node instanceof Node\Stmt) {
				$stmts[] = $node;
				continue;
			}
			if ($node instanceof Node\Stmt\Nop || $node instanceof Node\Stmt\InlineHTML) {
				continue;
			}
			if (!$node instanceof Node\Stmt) {
				continue;
			}
			$stmts[] = $node;
			$isPassedUnreachableStatement = true;
		}
		return $stmts;
	}

}
