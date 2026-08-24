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
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\ExecutionEndNode;
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
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
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
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function max;
use function sprintf;
use function usort;

#[AutowiredService]
class NodeScopeResolver
{

	public const LOOP_SCOPE_ITERATIONS = 3;
	public const GENERALIZE_AFTER_ITERATION = 1;

	/** @var array<string, true> filePath(string) => bool(true) */
	private array $analysedFiles = [];

	private ?ExpressionResultStorageStack $expressionResultStorageStack = null;

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
		protected readonly Container $container,
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
		protected readonly ExpressionResultFactory $expressionResultFactory,
	)
	{
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

		$expressionResultStorage = new ExpressionResultStorage();
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

	public function storeExpressionResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $expressionResult): void
	{
		// The storage only ever answers type questions from NodeCallbackScope, which
		// resolves them from the before-scope. Storing just the before-scope
		// keeps the storage from pinning throw points, impure points, scope
		// callbacks and the after-scope of every expression until the end of
		// the file.
		$storage->storeBeforeScope($expr, $expressionResult->getBeforeScope());
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
				// reproduces the previous pass's exit, so the verification walk is
				// skipped
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
		return $this->processStmtNodesInternal(
			$parentNode,
			$stmts,
			$scope,
			$storage,
			$nodeCallback,
			$context,
		)->toPublic();
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
		return $this->doProcessStmtNodes($parentNode, $stmts, $scope, $storage, $nodeCallback, $context);
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

		$stmtScope = $scope;
		if ($stmt instanceof Node\Stmt\Expression && $stmt->expr instanceof Expr\Throw_) {
			$stmtScope = $this->processStmtVarAnnotation($scope, $storage, $stmt, $stmt->expr->expr, $nodeCallback);
		}
		if ($stmt instanceof Return_) {
			$stmtScope = $this->processStmtVarAnnotation($scope, $storage, $stmt, $stmt->expr, $nodeCallback);
		}

		// Statements whose work is processing their expressions emit their node
		// callback AFTER that processing, inside their branches below, with the
		// entry scope - a synchronously invoked rule (the plain resolver,
		// PHP < 8.1) then finds the expressions' results in the storage instead
		// of re-walking them on demand, mirroring processExprNodeInternal().
		$deferredStmtCallback = $stmt instanceof Return_ || $stmt instanceof Node\Stmt\Expression || $stmt instanceof Echo_
			|| $stmt instanceof If_ || $stmt instanceof Switch_ || $stmt instanceof Foreach_;
		if (!$deferredStmtCallback) {
			$this->callNodeCallback($nodeCallback, $stmt, $stmtScope, $storage);
		}

		$stmtHandler = StmtHandlerRegistry::resolve($stmt, $this->container);
		if ($stmtHandler !== null) {
			$stmtResult = $stmtHandler->processStmt($this, $stmt, $stmtScope, $storage, $nodeCallback, $context);
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
			// answer) pair as at a pre-order emission - under fibers a pre-order
			// rule parks on its first ask and resumes at this store anyway - but
			// a synchronously invoked rule (the plain resolver, PHP < 8.1) now
			// finds the node's and its subtree's results in the storage instead
			// of re-walking them on demand.
			$this->callNodeCallbackWithExpression($nodeCallback, $expr, $scope, $storage, $context);
			// the call is now processed and stored; emit a virtual node so
			// impossible-check rules run on the fully processed call instead of
			// asking the scope before the call node itself is processed
			if ($expr instanceof FuncCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new FunctionCallExpressionNode($expr), $scope, $storage, $context);
			} elseif ($expr instanceof MethodCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new MethodCallExpressionNode($expr), $scope, $storage, $context);
			} elseif ($expr instanceof StaticCall) {
				$this->callNodeCallbackWithExpression($nodeCallback, new StaticMethodCallExpressionNode($expr), $scope, $storage, $context);
			}
			return $expressionResult;
		}

		$expressionResult = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
		$this->storeExpressionResult($storage, $expr, $expressionResult);

		return $expressionResult;
	}

	/**
	 * Unlike a method call, a property read defaults to pure: only a hook we're
	 * certain about and that is certainly side-effecting makes the read impure.
	 *
	 * The reset is assumed pure as reporting those would make accessing them
	 * unreasonably annoying.
	 *
	 * @param 'get'|'set' $hookName
	 * @return ImpurePoint[]
	 */
	public function getImpurePointsFromPropertyHook(
		MutatingScope $scope,
		PropertyFetch $propertyFetch,
		PhpPropertyReflection $propertyReflection,
		string $hookName,
	): array
	{
		if ($this->isPropertyHookBackingValueAccess($scope, $propertyFetch)) {
			return [];
		}

		if (!$propertyReflection->hasHook($hookName)) {
			return [];
		}

		if (!$propertyReflection->getHook($hookName)->hasSideEffects()->yes()) {
			return [];
		}

		return [
			new ImpurePoint(
				$scope,
				$propertyFetch,
				'propertyHookCall',
				sprintf(
					'call to %s hook of property %s::$%s',
					$hookName,
					$propertyReflection->getDeclaringClass()->getDisplayName(),
					$propertyReflection->getName(),
				),
				true,
			),
		];
	}

	/**
	 * Inside a hook of the same property, $this->prop is the backing value, not
	 * a re-entrant hook call.
	 */
	private function isPropertyHookBackingValueAccess(MutatingScope $scope, PropertyFetch $propertyFetch): bool
	{
		$scopeFunction = $scope->getFunction();

		return $scopeFunction instanceof PhpMethodFromParserNodeReflection
			&& $scopeFunction->isPropertyHook()
			&& $propertyFetch->var instanceof Variable
			&& $propertyFetch->var->name === 'this'
			&& $propertyFetch->name instanceof Identifier
			&& $propertyFetch->name->toString() === $scopeFunction->getHookedPropertyName();
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
	 * Replays a recorded convergence pass's emissions through the real node
	 * callback in place of the final loop walk. The pass's storage was merged
	 * into $storage by the caller; binding it for the whole replay lets the
	 * recorded scopes answer rule asks from the stored before-scopes, the
	 * same way the repeated walk's per-emission binding would.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function replayRecording(RecordingNodeCallback $recording, callable $nodeCallback, ExpressionResultStorage $storage): void
	{
		$stack = $this->getExpressionResultStorageStack();
		$stack->push($storage);
		try {
			$recording->replayThrough($nodeCallback);
		} finally {
			$stack->pop();
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

		if ($nodeCallback instanceof RecordingNodeCallback) {
			// recording never asks about types - the pairs are wrapped and
			// bound to the storage at replay time instead
			$nodeCallback($node, $scope);
			return;
		}

		// post-order emission means the node's own result and every subnode
		// result are already stored when the callback fires - NodeCallbackScope
		// answers every ask synchronously from the storage; the emitting
		// walk's storage is bound for the duration of the callback
		$stack = $this->getExpressionResultStorageStack();
		$stack->push($storage);
		try {
			$nodeCallback($node, $scope->toNodeCallbackScope());
		} finally {
			$stack->pop();
		}
	}

	private function getExpressionResultStorageStack(): ExpressionResultStorageStack
	{
		return $this->expressionResultStorageStack ??= $this->container->getByType(ExpressionResultStorageStack::class);
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
					$inAssignRightSideType = $scope->getType($inAssignRightSideExpr);
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
					$inAssignRightSideNativeType = $scope->getNativeType($inAssignRightSideExpr);
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
			$intermediaryClosureScopeResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $bodyRecording, StatementContext::createDeep());
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
		if (
			$replayBodyRecording !== null && $replayPassStorage !== null
			&& $replayPassResult !== null && $replayEntryScope !== null
			&& $closureScope->equals($replayEntryScope)
		) {
			// the final walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's result
			// and replay its emissions through the gathering callback instead.
			// The pass's own entry scope takes over: the recorded pairs carry
			// its anonymous-function reflection, which the gathering filter
			// compares by identity (the state is equals-identical anyway).
			$closureScope = $replayEntryScope;
			$originalStorage->mergeResults($replayPassStorage);
			$this->replayRecording($replayBodyRecording, $closureStmtsCallback, $originalStorage);
			$statementResult = $replayPassResult;
		} else {
			$statementResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $closureStmtsCallback, StatementContext::createTopLevel());
		}
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
	 * The refined closure type built from the single body walk, swapped onto the
	 * closure scope so ClosureReturnStatementsNode's rules see the refined
	 * expected return instead of the shallow entry reflection.
	 *
	 * @param list<array{Node\Stmt\Return_, Scope}> $gatheredReturnStatementsWithScope
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
			false,
			false,
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
		// run, build the refined arrow function type from the walked body (no
		// second walk) and fire InArrowFunctionNode with it, so the node and the
		// return-type rules see the refined expected return. The build must not
		// write the type cache: its values reflect this call's (possibly
		// extension-overridden) parameter typing while its key would match a
		// plain pricing ask.
		$refinedArrowFunctionType = $this->container->getByType(ClosureTypeResolver::class)->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowFunctionScope,
			$closureTypeThrowPoints,
			$closureTypeImpurePoints,
			$invalidateExpressions,
			false,
			false,
		);
		$refinedArrowFunctionScope = $arrowFunctionScope->withAnonymousFunctionReflection($refinedArrowFunctionType);
		$this->callNodeCallback($nodeCallback, new InArrowFunctionNode($refinedArrowFunctionType, $expr), $refinedArrowFunctionScope, $storage);

		return new ProcessArrowFunctionResult(
			$this->expressionResultFactory->create($scope, beforeScope: $scope, expr: $expr, hasYield: false, isAlwaysTerminating: $exprResult->isAlwaysTerminating(), throwPoints: $exprResult->getThrowPoints(), impurePoints: $exprResult->getImpurePoints()),
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
	public function createCallableParameters(Scope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType): ?array
	{
		return $this->doCreateCallableParameters($scope, $closureExpr, $args, $passedToType, static fn (Scope $s, Expr $e) => $s->getType($e));
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @return ParameterReflection[]|null
	 */
	public function createNativeCallableParameters(Scope $scope, Expr $closureExpr, ?array $args, ?Type $nativePassedToType): ?array
	{
		return $this->doCreateCallableParameters($scope, $closureExpr, $args, $nativePassedToType, static fn (Scope $s, Expr $e) => $s->getNativeType($e));
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @param Closure(Scope, Expr): Type $typeGetter
	 * @return ParameterReflection[]|null
	 */
	private function doCreateCallableParameters(Scope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType, Closure $typeGetter): ?array
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

		// The intrinsic argument overrides (array_map/filter/walk/find, curl_setopt,
		// implode, Closure::bind) rewrite a callback parameter's type from its
		// sibling arguments. Apply them up front on the entry scope - the parameter
		// pushed on the in-function-call stack while each argument is processed (and
		// priced, e.g. a closure's inferred return type) must be the overridden one,
		// exactly as when the caller pre-selected via selectFromArgs().
		$parametersAcceptors = ParametersAcceptorSelector::applyIntrinsicArgOverrides(
			$args,
			$parametersAcceptors,
			$namedArgumentsVariants,
			$scope,
			static fn (Expr $e): Type => $scope->getType($e),
			static fn (Expr $e): Type => $scope->getNativeType($e),
			static fn (Type $t): Type => $scope->getIterableValueType($t),
			static fn (Type $t): Type => $scope->getIterableKeyType($t),
		);

		// Metadata acceptor base - NO forward read. The per-argument resolution below picks the
		// count-correct variant (the by-ref/variadic STRUCTURE is variant-stable except where it is
		// keyed off the argument count, e.g. sscanf - and the count is known structurally) and
		// resolves generic parameter types from the args gathered so far; the call's return type
		// comes from the post-loop resolved acceptor.
		$metadataAcceptor = $parametersAcceptors[0] ?? null;

		// Both predicates are hoisted out of the per-argument loop - they traverse
		// the acceptor's parameter/return types.
		$hasTemplateParameterType = $metadataAcceptor !== null
			&& ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType($metadataAcceptor);
		$argMetadataIsTypeDriven = count($parametersAcceptors) > 1 || $hasTemplateParameterType;

		// Whether selecting an acceptor is type-driven at all: multiple variants to
		// choose between, templates or conditionals to resolve from the arg types,
		// or named-argument variants. When it is not, the gathered arg types can
		// never influence the selected acceptor, so the faithful-return gather walk
		// of a closure/arrow argument (gatherClosureArgType()) would be pure waste -
		// a plain mixed keeps the count/name bookkeeping correct.
		$typeDrivenAcceptorSelection = count($parametersAcceptors) > 1
			|| $namedArgumentsVariants !== null
			|| $hasTemplateParameterType
			|| ($metadataAcceptor !== null && $metadataAcceptor->getReturnType()->hasTemplateOrLateResolvableType());

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
					: new MixedType();
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
				// the preferred ClosureType read below now answers from this seed
				// instead of walking the body again (unless a parked fiber may
				// still complete the gathered data - then it keeps re-walking)
				$this->container->getByType(ClosureTypeResolver::class)->seedCacheFromClosureWalk($scopeToPass, $arg->value, $closureResult);
				if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
					$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $closureResult->getThrowPoints()));
					$impurePoints = array_merge($impurePoints, $closureResult->getImpurePoints());
				}

				$this->storeExpressionResult($storage, $arg->value, $this->expressionResultFactory->create(
					$closureResult->getScope(),
					$scopeToPass,
					$arg->value,
					hasYield: false,
					isAlwaysTerminating: false,
					throwPoints: [],
					impurePoints: [],
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
				// Prefer the invalidate expressions collected on the ClosureType: those
				// are gathered with the closure's pending fibers flushed, so they also
				// cover writes that go through a parked fiber (e.g. $this->prop[] = ...),
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
			} elseif ($arg->value instanceof Expr\ArrowFunction) {

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
				$processArrowFunctionResult = $this->processArrowFunctionNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $parameterType, $parameterNativeType);
				// the invalidation read below now answers from this seed instead
				// of walking the body again (unless a parked fiber may still
				// complete the gathered data - then it keeps re-walking)
				$this->container->getByType(ClosureTypeResolver::class)->seedCacheFromArrowFunctionWalk($scopeToPass, $arg->value, $processArrowFunctionResult);
				$arrowFunctionResult = $processArrowFunctionResult->getExpressionResult();
				if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
					$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $arrowFunctionResult->getThrowPoints()));
					$impurePoints = array_merge($impurePoints, $arrowFunctionResult->getImpurePoints());
				}
				if ($this->shouldInvalidateCallbackExpressions($parameter)) {
					$arrowFunctionType = $scope->getType($arg->value);
					if ($arrowFunctionType instanceof ClosureType) {
						$deferredInvalidateExpressions[] = [$arrowFunctionType->getInvalidateExpressions(), $arrowFunctionType->getUsedVariables()];
					}
				}
				$this->storeExpressionResult($storage, $arg->value, $arrowFunctionResult);
			} else {
				$exprType = $scope->getType($arg->value);
				$enterExpressionAssignForByRef = $assignByReference && $arg->value instanceof ArrayDimFetch && $arg->value->dim === null;
				if ($enterExpressionAssignForByRef) {
					$scopeToPass = $scopeToPass->enterExpressionAssign($arg->value);
				}
				$exprResult = $this->processExprNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context->enterDeep());
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

				$gatheredArgTypeByIndex[$i] = $exprType;
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
		// mirroring the original selectFromArgs(). When the selection is not
		// type-driven, the single (already-overridden) acceptor IS the resolved
		// acceptor - the fast path selectFromArgs() used to take.
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
		if ($metadataAcceptor !== null && $argMetadataIsTypeDriven) {
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
					$argType = $scope->getType($arg->value);
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
			$this->expressionResultFactory->create($scope, $scope, $callLike, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints),
			$resolvedAcceptor,
		);
	}

	/**
	 * Applies the intrinsic argument overrides (array_map/filter/walk/find,
	 * curl_setopt, implode, Closure::bind) on the arg-to-arg evolved scope,
	 * then type-selects the metadata acceptor over
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
			static fn (Expr $e): Type => $scope->getType($e),
			static fn (Expr $e): Type => $scope->getNativeType($e),
			static fn (Type $t): Type => $scope->getIterableValueType($t),
			static fn (Type $t): Type => $scope->getIterableKeyType($t),
		);

		return $this->selectArgsAcceptor($gatheredTypes, $overridden, $namedArgumentsVariants, $hasName, $unpack);
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

		return $scope->getType($closureExpr);
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
	public function processVirtualAssign(MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, Expr $var, Expr $assignedExpr, callable $nodeCallback): ExpressionResult
	{
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
			$this->expressionResultFactory->create($target->getScope(), beforeScope: $target->getScope(), expr: $assignedExpr, hasYield: false, isAlwaysTerminating: false, throwPoints: [], impurePoints: []),
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

				$scope = $scope->assignVariable(
					$name,
					$varTag->getType(),
					$scope->getNativeType($variableNode),
					$certainty,
				);
			}
		}

		if (count($variableLessTags) === 1 && $defaultExpr !== null) {
			$originalType = $scope->getType($defaultExpr);
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
