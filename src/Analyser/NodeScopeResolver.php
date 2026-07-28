<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use ArrayAccess;
use Closure;
use IteratorAggregate;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\ConstFetch;
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
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\ExprHandler\AssignHandler;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Node\BreaklessWhileLoopNode;
use PHPStan\Node\CatchWithUnthrownExceptionNode;
use PHPStan\Node\ClassConstantsNode;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\ClassStatementsGatherer;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\DeepNodeCloner;
use PHPStan\Node\DoWhileLoopConditionNode;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\FinallyExitPointsNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InClassNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Node\InForeachNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\PropertyHookStatementNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Parser\ArrowFunctionArgVisitor;
use PHPStan\Parser\ClosureArgVisitor;
use PHPStan\Parser\GotoLabelVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Parser\LineAttributesVisitor;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use Throwable;
use Traversable;
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
use function strtolower;
use function trim;
use function usort;
use const PHP_VERSION_ID;

#[AutowiredService]
class NodeScopeResolver
{

	private const LOOP_SCOPE_ITERATIONS = 3;
	private const GENERALIZE_AFTER_ITERATION = 1;
	private const FOREACH_UNROLL_LIMIT = 16;
	private const FOREACH_UNROLL_NESTED_LIMIT = 8;

	/** @var array<string, true> filePath(string) => bool(true) */
	private array $analysedFiles = [];

	/** @var array<string, true> */
	private array $calledMethodStack = [];

	/** @var array<string, MutatingScope|null> */
	private array $calledMethodResults = [];

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
	 */
	public function __construct(
		private readonly Container $container,
		private readonly ReflectionProvider $reflectionProvider,
		private readonly InitializerExprTypeResolver $initializerExprTypeResolver,
		private readonly Reflector $reflector,
		private readonly ClassReflectionFactory $classReflectionFactory,
		#[AutowiredExtensions(of: FunctionParameterOutTypeExtension::class)]
		private readonly ExtensionsCollection $functionParameterOutTypeExtensions,
		#[AutowiredExtensions(of: MethodParameterOutTypeExtension::class)]
		private readonly ExtensionsCollection $methodParameterOutTypeExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterOutTypeExtension::class)]
		private readonly ExtensionsCollection $staticMethodParameterOutTypeExtensions,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private readonly Parser $parser,
		private readonly FileTypeMapper $fileTypeMapper,
		private readonly PhpDocInheritanceResolver $phpDocInheritanceResolver,
		private readonly FileHelper $fileHelper,
		private readonly TypeSpecifier $typeSpecifier,
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
		private readonly ScopeFactory $scopeFactory,
		private readonly DeepNodeCloner $deepNodeCloner,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithLoopInitialAssignments,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithAlwaysIterableForeach,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithBlock,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private readonly bool $implicitThrows,
		#[AutowiredParameter]
		private readonly bool $treatPhpDocTypesAsCertain,
		private readonly ImplicitToStringCallHelper $implicitToStringCallHelper,
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

		$this->processPendingFibers($expressionResultStorage);
	}

	public function storeExpressionResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $expressionResult): void
	{
	}

	protected function processPendingFibers(ExpressionResultStorage $storage): void
	{
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
		do {
			$prevScope = $bodyScope;
			if ($mergeBodyScopeEachIteration) {
				$bodyScope = $bodyScope->mergeWith($scope);
			}
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
	private function processStmtNodesInternal(
		Node $parentNode,
		array $stmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$statementResult = $this->processStmtNodesInternalWithoutFlushingPendingFibers(
			$parentNode,
			$stmts,
			$scope,
			$storage,
			$nodeCallback,
			$context,
		);
		$this->processPendingFibers($storage);

		return $statementResult;
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processStmtNodesInternalWithoutFlushingPendingFibers(
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

		$this->callNodeCallback($nodeCallback, $stmt, $stmtScope, $storage);

		if ($stmt instanceof Node\Stmt\Declare_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$alwaysTerminating = false;
			$exitPoints = [];
			foreach ($stmt->declares as $declare) {
				$this->callNodeCallback($nodeCallback, $declare, $scope, $storage);
				$this->callNodeCallback($nodeCallback, $declare->value, $scope, $storage);
				if (
					$declare->key->name !== 'strict_types'
					|| !($declare->value instanceof Node\Scalar\Int_)
					|| $declare->value->value !== 1
				) {
					continue;
				}

				$scope = $scope->enterDeclareStrictTypes();
			}

			if ($stmt->stmts !== null) {
				$result = $this->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$alwaysTerminating = $result->isAlwaysTerminating();
				$exitPoints = $result->getExitPoints();
			}

			return new InternalStatementResult($scope, $hasYield, $alwaysTerminating, $exitPoints, $throwPoints, $impurePoints);
		} elseif ($stmt instanceof Node\Stmt\Function_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
			[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, , $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts,, $phpDocParameterOutTypes, , , , $pureUnlessCallableIsImpureParameters] = $this->getPhpDocs($scope, $stmt);

			foreach ($stmt->params as $param) {
				$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
			}

			if ($stmt->returnType !== null) {
				$this->callNodeCallback($nodeCallback, $stmt->returnType, $scope, $storage);
			}

			if (!$isDeprecated) {
				[$isDeprecated, $deprecatedDescription] = $this->getDeprecatedAttribute($scope, $stmt);
			}

			$functionScope = $scope->enterFunction(
				$stmt,
				$templateTypeMap,
				$phpDocParameterTypes,
				$phpDocReturnType,
				$phpDocThrowType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isPure,
				$acceptsNamedArguments,
				$asserts,
				$phpDocComment,
				$phpDocParameterOutTypes,
				$phpDocImmediatelyInvokedCallableParameters,
				$phpDocClosureThisTypeParameters,
				$pureUnlessCallableIsImpureParameters,
			);
			$functionReflection = $functionScope->getFunction();
			if (!$functionReflection instanceof PhpFunctionFromParserNodeReflection) {
				throw new ShouldNotHappenException();
			}

			$this->callNodeCallback($nodeCallback, new InFunctionNode($functionReflection, $stmt), $functionScope, $storage);

			$gatheredReturnStatements = [];
			$gatheredYieldStatements = [];
			$executionEnds = [];
			$functionImpurePoints = [];
			$statementResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $functionScope, $storage, new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($functionScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds, &$functionImpurePoints): void {
				if ($scope->getFunction() !== $functionScope->getFunction()) {
					return;
				}
				if ($scope->isInAnonymousFunction()) {
					return;
				}
				if ($node instanceof PropertyAssignNode) {
					$functionImpurePoints[] = new ImpurePoint(
						$scope,
						$node,
						'propertyAssign',
						'property assignment',
						true,
					);
					return;
				}
				if ($node instanceof ExecutionEndNode) {
					$executionEnds[] = $node;
					return;
				}
				if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
					$gatheredYieldStatements[] = $node;
				}
				if (!$node instanceof Return_) {
					return;
				}

				$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			}, $nodeCallback), StatementContext::createTopLevel())->toPublic();

			$this->callNodeCallback($nodeCallback, new FunctionReturnStatementsNode(
				$stmt,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$statementResult,
				$executionEnds,
				array_merge($statementResult->getImpurePoints(), $functionImpurePoints),
				$functionReflection,
			), $functionScope, $storage);
			if (!$scope->isInAnonymousFunction()) {
				$this->processPendingFibers($storage);
			}

			// declaring the function defines it in global state, so a negative
			// function_exists() narrowing that may refer to that function must be forgotten
			$scope = $scope->invalidateExistenceCheckExpressions(['function_exists'], $functionReflection->getName());
		} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
			[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes, , , , $pureUnlessCallableIsImpureParameters] = $this->getPhpDocs($scope, $stmt);

			foreach ($stmt->params as $param) {
				$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
			}

			if ($stmt->returnType !== null) {
				$this->callNodeCallback($nodeCallback, $stmt->returnType, $scope, $storage);
			}

			if (!$isDeprecated) {
				[$isDeprecated, $deprecatedDescription] = $this->getDeprecatedAttribute($scope, $stmt);
			}

			$isFromTrait = $stmt->getAttribute('originalTraitMethodName') === '__construct';
			$isConstructor = $isFromTrait || $stmt->name->toLowerString() === '__construct';

			$methodScope = $scope->enterClassMethod(
				$stmt,
				$templateTypeMap,
				$phpDocParameterTypes,
				$phpDocReturnType,
				$phpDocThrowType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
				$isFinal,
				$isPure,
				$acceptsNamedArguments,
				$asserts,
				$selfOutType,
				$phpDocComment,
				$phpDocParameterOutTypes,
				$phpDocImmediatelyInvokedCallableParameters,
				$phpDocClosureThisTypeParameters,
				$isConstructor,
				null,
				$pureUnlessCallableIsImpureParameters,
			);

			if (!$scope->isInClass()) {
				throw new ShouldNotHappenException();
			}

			$classReflection = $scope->getClassReflection();

			if ($isConstructor) {
				foreach ($stmt->params as $param) {
					if ($param->flags === 0 && $param->hooks === []) {
						continue;
					}

					if (!$param->var instanceof Variable || !is_string($param->var->name) || $param->var->name === '') {
						throw new ShouldNotHappenException();
					}
					$phpDoc = null;
					if ($param->getDocComment() !== null) {
						$phpDoc = $param->getDocComment()->getText();
					}
					$this->callNodeCallback($nodeCallback, new ClassPropertyNode(
						$param->var->name,
						$param->flags,
						$param->type !== null ? ParserNodeTypeToPHPStanType::resolve($param->type, $classReflection) : null,
						null,
						$phpDoc,
						$phpDocParameterTypes[$param->var->name] ?? null,
						true,
						$isFromTrait,
						$param,
						$isReadOnly,
						$scope->isInTrait(),
						$classReflection->isReadOnly(),
						false,
						$classReflection,
					), $methodScope, $storage);
					$this->processPropertyHooks(
						$stmt,
						$param->type,
						$phpDocParameterTypes[$param->var->name] ?? null,
						$param->var->name,
						$param->hooks,
						$scope,
						$storage,
						$nodeCallback,
					);
					$methodScope = $methodScope->assignExpression(new PropertyInitializationExpr($param->var->name), new MixedType(), new MixedType());
				}
			}

			if ($stmt->getAttribute('virtual', false) === false) {
				$methodReflection = $methodScope->getFunction();
				if (!$methodReflection instanceof PhpMethodFromParserNodeReflection) {
					throw new ShouldNotHappenException();
				}
				$this->callNodeCallback($nodeCallback, new InClassMethodNode($classReflection, $methodReflection, $stmt), $methodScope, $storage);
			}

			if ($stmt->stmts !== null) {
				$gatheredReturnStatements = [];
				$gatheredYieldStatements = [];
				$executionEnds = [];
				$methodImpurePoints = [];
				$statementResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $methodScope, $storage, new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($methodScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds, &$methodImpurePoints): void {
					if ($scope->getFunction() !== $methodScope->getFunction()) {
						return;
					}
					if ($scope->isInAnonymousFunction()) {
						return;
					}
					if ($node instanceof PropertyAssignNode) {
						if (
							$node->getPropertyFetch() instanceof Expr\PropertyFetch
							&& $scope->getFunction() instanceof PhpMethodFromParserNodeReflection
							&& $scope->getFunction()->getDeclaringClass()->hasConstructor()
							&& $scope->getFunction()->getDeclaringClass()->getConstructor()->getName() === $scope->getFunction()->getName()
							&& TypeUtils::findThisType($scope->getType($node->getPropertyFetch()->var)) !== null
						) {
							return;
						}
						$methodImpurePoints[] = new ImpurePoint(
							$scope,
							$node,
							'propertyAssign',
							'property assignment',
							true,
						);
						return;
					}
					if ($node instanceof ExecutionEndNode) {
						$executionEnds[] = $node;
						return;
					}
					if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
						$gatheredYieldStatements[] = $node;
					}
					if (!$node instanceof Return_) {
						return;
					}

					$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
				}, $nodeCallback), StatementContext::createTopLevel())->toPublic();

				$methodReflection = $methodScope->getFunction();
				if (!$methodReflection instanceof PhpMethodFromParserNodeReflection) {
					throw new ShouldNotHappenException();
				}

				$this->callNodeCallback($nodeCallback, new MethodReturnStatementsNode(
					$stmt,
					$gatheredReturnStatements,
					$gatheredYieldStatements,
					$statementResult,
					$executionEnds,
					array_merge($statementResult->getImpurePoints(), $methodImpurePoints),
					$classReflection,
					$methodReflection,
				), $methodScope, $storage);

				if ($isConstructor) {
					$finalScope = null;

					foreach ($executionEnds as $executionEnd) {
						if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
							continue;
						}

						$endScope = $executionEnd->getStatementResult()->getScope();
						if ($finalScope === null) {
							$finalScope = $endScope;
							continue;
						}

						$finalScope = $finalScope->mergeWith($endScope);
					}

					foreach ($gatheredReturnStatements as $statement) {
						if ($finalScope === null) {
							$finalScope = $statement->getScope()->toMutatingScope();
							continue;
						}

						$finalScope = $finalScope->mergeWith($statement->getScope()->toMutatingScope());
					}

					if ($finalScope !== null) {
						$scope = $finalScope->rememberConstructorScope();
					}

				}
			}
			if (!$scope->getClassReflection()->isAnonymous() && !$scope->isInAnonymousFunction()) {
				$this->processPendingFibers($storage);
			}
		} elseif ($stmt instanceof Echo_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			foreach ($stmt->exprs as $echoExpr) {
				$result = $this->processExprNode($stmt, $echoExpr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($echoExpr, $scope);
				$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			}

			$throwPoints = $overridingThrowPoints ?? $throwPoints;
			$impurePoints[] = new ImpurePoint($scope, $stmt, 'echo', 'echo', true);
			return new InternalStatementResult($scope, $hasYield, $isAlwaysTerminating, [], $throwPoints, $impurePoints);
		} elseif ($stmt instanceof Return_) {
			if ($stmt->expr !== null) {
				$result = $this->processExprNode($stmt, $stmt->expr, $stmtScope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
			} else {
				$hasYield = false;
				$throwPoints = [];
				$impurePoints = [];
			}

			return new InternalStatementResult($scope, $hasYield, true, [
				new InternalStatementExitPoint($stmt, $scope),
			], $overridingThrowPoints ?? $throwPoints, $impurePoints);
		} elseif ($stmt instanceof Continue_ || $stmt instanceof Break_) {
			if ($stmt->num !== null) {
				$result = $this->processExprNode($stmt, $stmt->num, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
			} else {
				$hasYield = false;
				$throwPoints = [];
				$impurePoints = [];
			}

			return new InternalStatementResult($scope, $hasYield, true, [
				new InternalStatementExitPoint($stmt, $scope),
			], $overridingThrowPoints ?? $throwPoints, $impurePoints);
		} elseif ($stmt instanceof Goto_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];

			return new InternalStatementResult($scope, $hasYield, true, [
				new InternalStatementExitPoint($stmt, $scope),
			], $overridingThrowPoints ?? $throwPoints, $impurePoints);
		} elseif ($stmt instanceof Node\Stmt\Label) {
			$hasYield = false;
			$throwPoints = $overridingThrowPoints ?? [];
			$impurePoints = [];
		} elseif ($stmt instanceof Node\Stmt\Expression) {
			if ($stmt->expr instanceof Expr\Throw_) {
				$scope = $stmtScope;
			}
			$earlyTerminationExpr = $this->findEarlyTerminatingExpr($stmt->expr, $scope);
			$hasAssign = false;
			$currentScope = $scope;
			$result = $this->processExprNode($stmt, $stmt->expr, $scope, $storage, new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($currentScope, &$hasAssign): void {
				if (
					!($node instanceof VariableAssignNode) && !($node instanceof PropertyAssignNode)
					|| $scope->getAnonymousFunctionReflection() !== $currentScope->getAnonymousFunctionReflection()
					|| $scope->getFunction() !== $currentScope->getFunction()
				) {
					return;
				}

				$hasAssign = true;
			}, $nodeCallback), ExpressionContext::createTopLevel());
			$throwPoints = array_filter($result->getThrowPoints(), static fn ($throwPoint) => $throwPoint->isExplicit());
			if (
				count($result->getImpurePoints()) === 0
				&& count($throwPoints) === 0
				&& !$stmt->expr instanceof Expr\PostInc
				&& !$stmt->expr instanceof Expr\PreInc
				&& !$stmt->expr instanceof Expr\PostDec
				&& !$stmt->expr instanceof Expr\PreDec
			) {
				$this->callNodeCallback($nodeCallback, new NoopExpressionNode($stmt->expr, $hasAssign), $scope, $storage);
			}
			$scope = $result->getScope();
			$scope = $scope->filterBySpecifiedTypes($this->typeSpecifier->specifyTypesInCondition(
				$scope,
				$stmt->expr,
				TypeSpecifierContext::createNull(),
			));
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();

			if ($earlyTerminationExpr !== null) {
				return new InternalStatementResult($scope, $hasYield, true, [
					new InternalStatementExitPoint($stmt, $scope),
				], $overridingThrowPoints ?? $throwPoints, $impurePoints);
			}
			return new InternalStatementResult($scope, $hasYield, $isAlwaysTerminating, [], $overridingThrowPoints ?? $throwPoints, $impurePoints);
		} elseif ($stmt instanceof Node\Stmt\Namespace_) {
			if ($stmt->name !== null) {
				$scope = $scope->enterNamespace($stmt->name->toString());
			} else {
				$scope = $scope->enterNamespace('');
			}

			$scope = $this->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context)->getScope();
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
		} elseif ($stmt instanceof Node\Stmt\Trait_) {
			// declaring the trait defines it in global state,
			// so a negative trait_exists() narrowing that may refer to that trait must be forgotten
			$name = $stmt->namespacedName ?? $stmt->name;
			$scope = $scope->invalidateExistenceCheckExpressions(['trait_exists'], $name instanceof Name ? $name->toString() : null);

			return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
		} elseif ($stmt instanceof Node\Stmt\ClassLike) {
			// declaring a class/interface/enum defines it in global state,
			// so a matching negative existence-check narrowing must be forgotten
			if ($stmt instanceof Node\Stmt\Interface_) {
				$existenceCheckFunctionNames = ['interface_exists'];
			} elseif ($stmt instanceof Node\Stmt\Enum_) {
				$existenceCheckFunctionNames = ['class_exists', 'enum_exists'];
			} else {
				$existenceCheckFunctionNames = ['class_exists'];
			}
			$name = $stmt->namespacedName ?? $stmt->name;
			$scope = $scope->invalidateExistenceCheckExpressions($existenceCheckFunctionNames, $name instanceof Name ? $name->toString() : null);

			if (!$context->isTopLevel()) {
				return new InternalStatementResult($scope, hasYield: false, isAlwaysTerminating: false, exitPoints: [], throwPoints: [], impurePoints: []);
			}
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			if (isset($stmt->namespacedName)) {
				$classReflection = $this->getCurrentClassReflection($stmt, $stmt->namespacedName->toString(), $scope);
				$classScope = $scope->enterClass($classReflection);
				$this->callNodeCallback($nodeCallback, new InClassNode($stmt, $classReflection), $classScope, $storage);
			} elseif ($stmt instanceof Class_) {
				if ($stmt->name === null) {
					throw new ShouldNotHappenException();
				}
				if (!$stmt->isAnonymous()) {
					$classReflection = $this->reflectionProvider->getClass($stmt->name->toString());
				} else {
					$classReflection = $this->reflectionProvider->getAnonymousClassReflection($stmt, $scope);
				}
				$classScope = $scope->enterClass($classReflection);
				$this->callNodeCallback($nodeCallback, new InClassNode($stmt, $classReflection), $classScope, $storage);
			} else {
				throw new ShouldNotHappenException();
			}

			$classStatementsGatherer = new ClassStatementsGatherer($classReflection, $nodeCallback);
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $classScope, $storage, $classStatementsGatherer);

			$classLikeStatements = $stmt->stmts;
			// analyze static methods first; constructor next; instance methods and property hooks last so we can carry over the scope
			usort($classLikeStatements, static function ($a, $b) {
				if ($a instanceof Node\Stmt\Property) {
					return 1;
				}
				if ($b instanceof Node\Stmt\Property) {
					return -1;
				}

				if (!$a instanceof Node\Stmt\ClassMethod || !$b instanceof Node\Stmt\ClassMethod) {
					return 0;
				}

				return [!$a->isStatic(), $a->name->toLowerString() !== '__construct'] <=> [!$b->isStatic(), $b->name->toLowerString() !== '__construct'];
			});

			$this->processStmtNodesInternal($stmt, $classLikeStatements, $classScope, $storage, $classStatementsGatherer, $context);
			$this->callNodeCallback($nodeCallback, new ClassPropertiesNode($stmt, $this->readWritePropertiesExtensions, $classStatementsGatherer->getProperties(), $classStatementsGatherer->getPropertyUsages(), $classStatementsGatherer->getMethodCalls(), $classStatementsGatherer->getReturnStatementsNodes(), $classStatementsGatherer->getPropertyAssigns(), $classReflection), $classScope, $storage);
			$this->callNodeCallback($nodeCallback, new ClassMethodsNode($stmt, $classStatementsGatherer->getMethods(), $classStatementsGatherer->getMethodCalls(), $classReflection), $classScope, $storage);
			$this->callNodeCallback($nodeCallback, new ClassConstantsNode($stmt, $classStatementsGatherer->getConstants(), $classStatementsGatherer->getConstantFetches(), $classReflection), $classScope, $storage);
			$classReflection->evictPrivateSymbols();
			$this->calledMethodResults = [];
		} elseif ($stmt instanceof Node\Stmt\Property) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);

			$nativePropertyType = $stmt->type !== null ? ParserNodeTypeToPHPStanType::resolve($stmt->type, $scope->getClassReflection()) : null;

			[,,,,,,,,,,,,$isReadOnly, $docComment, ,,,$varTags, $isAllowedPrivateMutation] = $this->getPhpDocs($scope, $stmt);
			$phpDocType = null;
			if (isset($varTags[0]) && count($varTags) === 1) {
				$phpDocType = $varTags[0]->getType();
			}

			foreach ($stmt->props as $prop) {
				$this->callNodeCallback($nodeCallback, $prop, $scope, $storage);
				if ($prop->default !== null) {
					$this->processExprNode($stmt, $prop->default, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				}

				if (!$scope->isInClass()) {
					throw new ShouldNotHappenException();
				}
				$propertyName = $prop->name->toString();

				if ($phpDocType === null) {
					if (isset($varTags[$propertyName])) {
						$phpDocType = $varTags[$propertyName]->getType();
					}
				}

				$propStmt = clone $stmt;
				$propStmt->setAttributes($prop->getAttributes());
				$propStmt->setAttribute('originalPropertyStmt', $stmt);
				$this->callNodeCallback(
					$nodeCallback,
					new ClassPropertyNode(
						$propertyName,
						$stmt->flags,
						$nativePropertyType,
						$prop->default,
						$docComment,
						$phpDocType,
						false,
						false,
						$propStmt,
						$isReadOnly,
						$scope->isInTrait(),
						$scope->getClassReflection()->isReadOnly(),
						$isAllowedPrivateMutation,
						$scope->getClassReflection(),
					),
					$scope,
					$storage,
				);
			}

			if (count($stmt->hooks) > 0) {
				if (!isset($propertyName)) {
					throw new ShouldNotHappenException('Property name should be known when analysing hooks.');
				}
				$this->processPropertyHooks(
					$stmt,
					$stmt->type,
					$phpDocType,
					$propertyName,
					$stmt->hooks,
					$scope,
					$storage,
					$nodeCallback,
				);
			}

			if ($stmt->type !== null) {
				$this->callNodeCallback($nodeCallback, $stmt->type, $scope, $storage);
			}
		} elseif ($stmt instanceof If_) {
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$conditionType = ($this->treatPhpDocTypesAsCertain ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
			$ifAlwaysTrue = $conditionType->isTrue()->yes();
			$exitPoints = [];
			$throwPoints = $overridingThrowPoints ?? $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			$endStatements = [];
			$finalScope = null;
			$alwaysTerminating = true;
			$hasYield = $condResult->hasYield();

			$branchScopeStatementResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $condResult->getTruthyScope(), $storage, $nodeCallback, $context);

			if (!$conditionType->isTrue()->no()) {
				$exitPoints = $branchScopeStatementResult->getExitPoints();
				$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $branchScopeStatementResult->getImpurePoints());
				$branchScope = $branchScopeStatementResult->getScope();
				$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? null : $branchScope;
				$alwaysTerminating = $branchScopeStatementResult->isAlwaysTerminating();
				if (count($branchScopeStatementResult->getEndStatements()) > 0) {
					$endStatements = array_merge($endStatements, $branchScopeStatementResult->getEndStatements());
				} elseif (count($stmt->stmts) > 0) {
					$endStatements[] = new InternalEndStatementResult($stmt->stmts[count($stmt->stmts) - 1], $branchScopeStatementResult);
				} else {
					$endStatements[] = new InternalEndStatementResult($stmt, $branchScopeStatementResult);
				}
				$hasYield = $branchScopeStatementResult->hasYield() || $hasYield;
			}

			$scope = $condResult->getFalseyScope();
			$lastElseIfConditionIsTrue = false;

			$condScope = $scope;
			foreach ($stmt->elseifs as $elseif) {
				$this->callNodeCallback($nodeCallback, $elseif, $scope, $storage);
				$condResult = $this->processExprNode($stmt, $elseif->cond, $condScope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$elseIfConditionType = ($this->treatPhpDocTypesAsCertain ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
				$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $condResult->getImpurePoints());
				$branchScopeStatementResult = $this->processStmtNodesInternal($elseif, $elseif->stmts, $condResult->getTruthyScope(), $storage, $nodeCallback, $context);

				if (
					!$ifAlwaysTrue
					&& !$lastElseIfConditionIsTrue
					&& !$elseIfConditionType->isTrue()->no()
				) {
					$exitPoints = array_merge($exitPoints, $branchScopeStatementResult->getExitPoints());
					$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $branchScopeStatementResult->getImpurePoints());
					$branchScope = $branchScopeStatementResult->getScope();
					$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope, true);
					$alwaysTerminating = $alwaysTerminating && $branchScopeStatementResult->isAlwaysTerminating();
					if (count($branchScopeStatementResult->getEndStatements()) > 0) {
						$endStatements = array_merge($endStatements, $branchScopeStatementResult->getEndStatements());
					} elseif (count($elseif->stmts) > 0) {
						$endStatements[] = new InternalEndStatementResult($elseif->stmts[count($elseif->stmts) - 1], $branchScopeStatementResult);
					} else {
						$endStatements[] = new InternalEndStatementResult($elseif, $branchScopeStatementResult);
					}
					$hasYield = $hasYield || $branchScopeStatementResult->hasYield();
				}

				if (
					$elseIfConditionType->isTrue()->yes()
				) {
					$lastElseIfConditionIsTrue = true;
				}

				$condScope = $condResult->getFalseyScope();
				$scope = $condScope;
			}

			if ($stmt->else === null) {
				if (!$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
					$finalScope = $scope->mergeWith($finalScope, true);
					$alwaysTerminating = false;
				}
			} else {
				$this->callNodeCallback($nodeCallback, $stmt->else, $scope, $storage);
				$branchScopeStatementResult = $this->processStmtNodesInternal($stmt->else, $stmt->else->stmts, $scope, $storage, $nodeCallback, $context);

				if (!$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
					$exitPoints = array_merge($exitPoints, $branchScopeStatementResult->getExitPoints());
					$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $branchScopeStatementResult->getImpurePoints());
					$branchScope = $branchScopeStatementResult->getScope();
					$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope, true);
					$alwaysTerminating = $alwaysTerminating && $branchScopeStatementResult->isAlwaysTerminating();
					if (count($branchScopeStatementResult->getEndStatements()) > 0) {
						$endStatements = array_merge($endStatements, $branchScopeStatementResult->getEndStatements());
					} elseif (count($stmt->else->stmts) > 0) {
						$endStatements[] = new InternalEndStatementResult($stmt->else->stmts[count($stmt->else->stmts) - 1], $branchScopeStatementResult);
					} else {
						$endStatements[] = new InternalEndStatementResult($stmt->else, $branchScopeStatementResult);
					}
					$hasYield = $hasYield || $branchScopeStatementResult->hasYield();
				}
			}

			if ($finalScope === null) {
				$finalScope = $scope;
			}

			if ($stmt->else === null && !$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
				$endStatements[] = new InternalEndStatementResult($stmt, new InternalStatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPoints, $throwPoints, $impurePoints));
			}

			return new InternalStatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPoints, $throwPoints, $impurePoints, $endStatements);
		} elseif ($stmt instanceof Node\Stmt\TraitUse) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];

			$traitStorage = $storage->duplicate();
			$traitStorage->pendingFibers = [];
			$this->processTraitUse($stmt, $scope, $traitStorage, $nodeCallback);
			$this->processPendingFibers($traitStorage);
		} elseif ($stmt instanceof Foreach_) {
			if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
				$scope = $this->processVarAnnotation($scope, [$stmt->expr->name], $stmt);
			}
			$condResult = $this->processExprNode($stmt, $stmt->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$throwPoints = $overridingThrowPoints ?? $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			$scope = $condResult->getScope();
			$arrayComparisonExpr = new BinaryOp\NotIdentical(
				$stmt->expr,
				new Array_([]),
			);
			$this->callNodeCallback($nodeCallback, new InForeachNode($stmt), $scope, $storage);
			$originalScope = $scope;
			$bodyScope = $scope;

			if ($stmt->keyVar instanceof Variable) {
				$keyTypeExpr = new NativeTypeExpr(
					$originalScope->getIterableKeyType($originalScope->getType($stmt->expr)),
					$originalScope->getIterableKeyType($originalScope->getNativeType($stmt->expr)),
				);
				$this->callNodeCallback($nodeCallback, new VariableAssignNode($stmt->keyVar, $keyTypeExpr), $originalScope, $storage);
			}

			if ($stmt->valueVar instanceof Variable) {
				$valueTypeExpr = new NativeTypeExpr(
					$originalScope->getIterableValueType($originalScope->getType($stmt->expr)),
					$originalScope->getIterableValueType($originalScope->getNativeType($stmt->expr)),
				);
				$this->callNodeCallback($nodeCallback, new VariableAssignNode($stmt->valueVar, $valueTypeExpr), $originalScope, $storage);
			} elseif ($stmt->valueVar instanceof List_) {
				$virtualAssign = new Assign($stmt->valueVar, new NativeTypeExpr(
					$originalScope->getIterableValueType($originalScope->getType($stmt->expr)),
					$originalScope->getIterableValueType($originalScope->getNativeType($stmt->expr)),
				));
				$virtualAssign->setAttributes($stmt->valueVar->getAttributes());
				$this->callNodeCallback($nodeCallback, $virtualAssign, $scope, $storage);
			}

			$originalStorage = $storage;
			$unrolledEndScope = null;
			$unrolledTotalKeys = null;
			$iterateeScope = $this->polluteScopeWithAlwaysIterableForeach ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope;
			if ($context->isTopLevel()) {
				$storage = $originalStorage->duplicate();

				$originalScope = $iterateeScope;
				$unrolledResult = $this->tryProcessUnrolledConstantArrayForeach($stmt, $originalScope, $originalStorage, $context);
				if ($unrolledResult !== null) {
					$bodyScope = $unrolledResult['bodyScope'];
					$unrolledEndScope = $unrolledResult['endScope'];
					$unrolledTotalKeys = $unrolledResult['totalKeys'];
				} else {
					$bodyScope = $this->enterForeach($originalScope, $storage, $originalScope, $stmt, $nodeCallback);
					$count = 0;
					do {
						$prevScope = $bodyScope;
						$bodyScope = $bodyScope->mergeWith($iterateeScope);
						$storage = $originalStorage->duplicate();
						$bodyScope = $this->enterForeach($bodyScope, $storage, $originalScope, $stmt, $nodeCallback);
						$bodyScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
						$bodyScope = $bodyScopeResult->getScope();
						foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
							$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
						}
						if ($bodyScope->equals($prevScope)) {
							break;
						}

						if ($count >= self::GENERALIZE_AFTER_ITERATION) {
							$bodyScope = $prevScope->generalizeWith($bodyScope);
						}
						$count++;
					} while ($count < self::LOOP_SCOPE_ITERATIONS);
				}
			}

			$bodyScope = $bodyScope->mergeWith($iterateeScope);
			$storage = $originalStorage;
			$bodyScope = $this->enterForeach($bodyScope, $storage, $originalScope, $stmt, $nodeCallback);
			$finalPassContext = $unrolledTotalKeys !== null ? $context->enterUnrolledForeach($unrolledTotalKeys) : $context;
			$finalScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $finalPassContext)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope();
			$scopesWithIterableValueType = [];

			$keyVarExpr = null;
			$originalKeyVarExpr = null;
			if ($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)) {
				$keyVarExpr = $stmt->keyVar;
				$originalKeyVarExpr = new OriginalForeachKeyExpr($stmt->keyVar->name);
			}
			$originalValueExpr = null;
			if ($stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name)) {
				$originalValueExpr = new OriginalForeachValueExpr($stmt->valueVar->name);
			}

			// With a key variable, each iteration is tracked through the original key
			// expression and the narrowed element is projected onto the array dim fetch.
			// Without one (`foreach ($a as $v)`) we instead track the original value
			// expression and rewrite the array value type directly from the value var.
			$trackingExpr = $originalKeyVarExpr ?? $originalValueExpr;

			$continueExitPointHasUnoriginalKeyType = false;
			if ($trackingExpr !== null) {
				if ($finalScope->hasExpressionType($trackingExpr)->yes()) {
					$scopesWithIterableValueType[] = $finalScope;
				} else {
					$continueExitPointHasUnoriginalKeyType = true;
				}
			}

			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$continueScope = $continueExitPoint->getScope();
				$finalScope = $continueScope->mergeWith($finalScope);
				if ($trackingExpr === null || !$continueScope->hasExpressionType($trackingExpr)->yes()) {
					$continueExitPointHasUnoriginalKeyType = true;
					continue;
				}
				$scopesWithIterableValueType[] = $continueScope;
			}
			$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
			foreach ($breakExitPoints as $breakExitPoint) {
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
			}

			if ($unrolledEndScope !== null) {
				$finalScope = $unrolledEndScope;
			}

			$exprType = $scope->getType($stmt->expr);
			$hasExpr = $scope->hasExpressionType($stmt->expr);
			if (
				count($breakExitPoints) === 0
				&& count($scopesWithIterableValueType) > 0
				&& !$continueExitPointHasUnoriginalKeyType
				&& ($keyVarExpr !== null || $originalValueExpr !== null)
				&& (!$hasExpr->no() || !$stmt->expr instanceof Variable)
				&& $exprType->isArray()->yes()
				&& $exprType->isConstantArray()->no()
			) {
				$arrayDimFetchLoopTypes = [];
				$arrayDimFetchLoopNativeTypes = [];
				$keyLoopTypes = [];
				$keyLoopNativeTypes = [];
				foreach ($scopesWithIterableValueType as $scopeWithIterableValueType) {
					if ($keyVarExpr !== null) {
						$arrayExprDimFetch = new ArrayDimFetch($stmt->expr, $keyVarExpr);
						$dimFetchType = $scopeWithIterableValueType->getType($arrayExprDimFetch);
						$dimFetchNativeType = $scopeWithIterableValueType->getNativeType($arrayExprDimFetch);
						// Condition-based narrowings like `is_string($type)` apply to the value
						// variable but not automatically to the array dim fetch, even though the
						// two describe the same element for a given iteration. If the value var
						// hasn't been reassigned (OriginalForeachValueExpr still tracked) we use
						// the narrowed value-var type in place of the broader dim fetch type so
						// the loop's final array rewrite below picks up the sharper element type.
						if ($originalValueExpr !== null && $scopeWithIterableValueType->hasExpressionType($originalValueExpr)->yes()) {
							// read the loop value variable's narrowed type directly by name -
							// it is an assigned (not processExprNode-processed) variable
							// ($originalValueExpr !== null implies a string-named Variable)
							$valueVarType = $scopeWithIterableValueType->getVariableType($stmt->valueVar->name);
							if ($dimFetchType->isSuperTypeOf($valueVarType)->yes()) {
								$dimFetchType = $valueVarType;
							}
							$valueVarNativeType = $scopeWithIterableValueType->getNativeType($stmt->valueVar);
							if ($dimFetchNativeType->isSuperTypeOf($valueVarNativeType)->yes()) {
								$dimFetchNativeType = $valueVarNativeType;
							}
						}
						$keyLoopTypes[] = $scopeWithIterableValueType->getType($keyVarExpr);
						$keyLoopNativeTypes[] = $scopeWithIterableValueType->getNativeType($keyVarExpr);
					} else {
						// No key variable: the narrowed value var is the array element type directly.
						$dimFetchType = $scopeWithIterableValueType->getVariableType($stmt->valueVar->name);
						$dimFetchNativeType = $scopeWithIterableValueType->getNativeType($stmt->valueVar);
					}
					$arrayDimFetchLoopTypes[] = $dimFetchType;
					$arrayDimFetchLoopNativeTypes[] = $dimFetchNativeType;
				}

				$arrayDimFetchLoopType = TypeCombinator::union(...$arrayDimFetchLoopTypes);
				$arrayDimFetchLoopNativeType = TypeCombinator::union(...$arrayDimFetchLoopNativeTypes);

				$valueTypeChanged = !$arrayDimFetchLoopType->equals($exprType->getIterableValueType());
				$keyTypeChanged = false;
				$keyLoopType = $exprType->getIterableKeyType();
				$keyLoopNativeType = $scope->getNativeType($stmt->expr)->getIterableKeyType();
				if ($keyVarExpr !== null) {
					$keyLoopType = TypeCombinator::union(...$keyLoopTypes);
					$keyLoopNativeType = TypeCombinator::union(...$keyLoopNativeTypes);
					$keyTypeChanged = !$keyLoopType->equals($exprType->getIterableKeyType());
				}

				if ($valueTypeChanged || $keyTypeChanged) {
					$newExprType = $exprType;
					if ($valueTypeChanged) {
						$newExprType = $newExprType->mapValueType(static fn (Type $type): Type => $arrayDimFetchLoopType);
					}
					if ($keyTypeChanged) {
						$newExprType = $newExprType->mapKeyType(static fn (Type $type): Type => $keyLoopType);
					}

					$nativeExprType = $scope->getNativeType($stmt->expr);
					$newExprNativeType = $nativeExprType;
					if ($valueTypeChanged) {
						$newExprNativeType = $newExprNativeType->mapValueType(static fn (Type $type): Type => $arrayDimFetchLoopNativeType);
					}
					if ($keyTypeChanged) {
						$newExprNativeType = $newExprNativeType->mapKeyType(static fn (Type $type): Type => $keyLoopNativeType);
					}

					if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
						$finalScope = $finalScope->assignVariable(
							$stmt->expr->name,
							$newExprType,
							$newExprNativeType,
							$hasExpr,
						);
					} else {
						$finalScope = $finalScope->assignExpression(
							$stmt->expr,
							$newExprType,
							$newExprNativeType,
						);
					}
				}
			}

			$isIterableAtLeastOnce = $exprType->isIterableAtLeastOnce();
			if ($isIterableAtLeastOnce->maybe() || $exprType->isIterable()->no()) {
				$finalScope = $finalScope->mergeWith($scope->filterByTruthyValue(new BooleanOr(
					new BinaryOp\Identical(
						$stmt->expr,
						new Array_([]),
					),
					new FuncCall(new Name\FullyQualified('is_object'), [
						new Arg($stmt->expr),
					]),
				)));
			} elseif ($isIterableAtLeastOnce->no() || $finalScopeResult->isAlwaysTerminating()) {
				$finalScope = $scope;
			} elseif (!$this->polluteScopeWithAlwaysIterableForeach) {
				$finalScope = $scope->processAlwaysIterableForeachScopeWithoutPollute($finalScope);
				// get types from finalScope, but don't create new variables
			}

			if (!$isIterableAtLeastOnce->no()) {
				$throwPoints = array_merge($throwPoints, $finalScopeResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $finalScopeResult->getImpurePoints());
			}
			$traversableThrowPoint = $this->getTraversableForeachThrowPoint($scope, $stmt->expr);
			if ($traversableThrowPoint !== null) {
				$throwPoints[] = $traversableThrowPoint;
			}
			if ($context->isTopLevel() && $stmt->byRef) {
				$finalScope = $finalScope->assignExpression(new ForeachValueByRefExpr($stmt->valueVar), new MixedType(), new MixedType());
			}

			return new InternalStatementResult(
				$finalScope,
				$finalScopeResult->hasYield() || $condResult->hasYield(),
				$isIterableAtLeastOnce->yes() && $finalScopeResult->isAlwaysTerminating(),
				$finalScopeResult->getExitPointsForOuterLoop(),
				$throwPoints,
				$impurePoints,
			);
		} elseif ($stmt instanceof While_) {
			$originalStorage = $storage;
			$storage = $originalStorage->duplicate();
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep());
			$beforeCondBooleanType = ($this->treatPhpDocTypesAsCertain ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
			$condScope = $condResult->getFalseyScope();
			if (!$context->isTopLevel() && $beforeCondBooleanType->isFalse()->yes()) {
				if (!$this->polluteScopeWithLoopInitialAssignments) {
					$scope = $condScope->mergeWith($scope);
				}

				return new InternalStatementResult(
					$scope,
					$condResult->hasYield(),
					false,
					[],
					$condResult->getThrowPoints(),
					$condResult->getImpurePoints(),
				);
			}
			$bodyScope = $condResult->getTruthyScope();

			if ($context->isTopLevel()) {
				$count = 0;
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($scope);
					$storage = $originalStorage->duplicate();
					$bodyScope = $this->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep())->getTruthyScope();
					$bodyScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}
					if ($bodyScope->equals($prevScope)) {
						break;
					}

					if ($count >= self::GENERALIZE_AFTER_ITERATION) {
						$bodyScope = $prevScope->generalizeWith($bodyScope);
					}
					$count++;
				} while ($count < self::LOOP_SCOPE_ITERATIONS);
			}

			$bodyScope = $bodyScope->mergeWith($scope);
			$bodyScopeMaybeRan = $bodyScope;
			$storage = $originalStorage;
			$bodyScope = $this->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep())->getTruthyScope();
			$finalScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope()->filterByFalseyValue($stmt->cond);

			$alwaysIterates = false;
			$neverIterates = false;
			if ($context->isTopLevel()) {
				$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $bodyScopeMaybeRan->getType($stmt->cond) : $bodyScopeMaybeRan->getNativeType($stmt->cond))->toBoolean();
				$alwaysIterates = $condBooleanType->isTrue()->yes();
				$neverIterates = $condBooleanType->isFalse()->yes();
			}
			if (!$alwaysIterates) {
				foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$finalScope = $finalScope->mergeWith($continueExitPoint->getScope());
				}
			}

			$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
			if (count($breakExitPoints) > 0) {
				$breakScope = $alwaysIterates ? null : $finalScope;
				foreach ($breakExitPoints as $breakExitPoint) {
					$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope());
				}
				$finalScope = $breakScope;
			}

			$isIterableAtLeastOnce = $beforeCondBooleanType->isTrue()->yes();
			$this->callNodeCallback($nodeCallback, new BreaklessWhileLoopNode($stmt, $finalScopeResult->toPublic()->getExitPoints(), $finalScopeResult->hasYield()), $bodyScopeMaybeRan, $storage);

			if ($alwaysIterates) {
				$isAlwaysTerminating = count($finalScopeResult->getExitPointsByType(Break_::class)) === 0;
			} elseif ($isIterableAtLeastOnce) {
				$isAlwaysTerminating = $finalScopeResult->isAlwaysTerminating();
			} else {
				$isAlwaysTerminating = false;
			}
			if (!$isIterableAtLeastOnce) {
				if (!$this->polluteScopeWithLoopInitialAssignments) {
					$condScope = $condScope->mergeWith($scope);
				}
				$finalScope = $finalScope->mergeWith($condScope);
			}

			$throwPoints = $overridingThrowPoints ?? $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			if (!$neverIterates) {
				$throwPoints = array_merge($throwPoints, $finalScopeResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $finalScopeResult->getImpurePoints());
			}

			return new InternalStatementResult(
				$finalScope,
				$finalScopeResult->hasYield() || $condResult->hasYield(),
				$isAlwaysTerminating,
				$finalScopeResult->getExitPointsForOuterLoop(),
				$throwPoints,
				$impurePoints,
			);
		} elseif ($stmt instanceof Do_) {
			$finalScope = null;
			$bodyScope = $scope;
			$count = 0;
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$originalStorage = $storage;

			if ($context->isTopLevel()) {
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($scope);
					$storage = $originalStorage->duplicate();
					$bodyScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
					$alwaysTerminating = $bodyScopeResult->isAlwaysTerminating();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}
					$finalScope = $alwaysTerminating ? $finalScope : $bodyScope->mergeWith($finalScope);
					foreach ($bodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
						$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
					}
					$bodyScope = $this->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep())->getTruthyScope();
					if ($bodyScope->equals($prevScope)) {
						break;
					}

					if ($count >= self::GENERALIZE_AFTER_ITERATION) {
						$bodyScope = $prevScope->generalizeWith($bodyScope);
					}
					$count++;
				} while ($count < self::LOOP_SCOPE_ITERATIONS);

				$bodyScope = $bodyScope->mergeWith($scope);
			}

			$storage = $originalStorage;
			$bodyScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
			$bodyScope = $bodyScopeResult->getScope();
			foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
			}

			$alwaysIterates = false;
			if ($context->isTopLevel()) {
				$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $bodyScope->getType($stmt->cond) : $bodyScope->getNativeType($stmt->cond))->toBoolean();
				$alwaysIterates = $condBooleanType->isTrue()->yes();
			}

			$this->callNodeCallback($nodeCallback, new DoWhileLoopConditionNode($stmt->cond, $bodyScopeResult->toPublic()->getExitPoints(), $bodyScopeResult->hasYield()), $bodyScope, $storage);

			if ($alwaysIterates) {
				$alwaysTerminating = count($bodyScopeResult->getExitPointsByType(Break_::class)) === 0;
			} else {
				$alwaysTerminating = $bodyScopeResult->isAlwaysTerminating();
			}
			$finalScope = $alwaysTerminating ? $finalScope : $bodyScope->mergeWith($finalScope);
			if ($finalScope === null) {
				$finalScope = $scope;
			}
			if (!$alwaysTerminating) {
				$condResult = $this->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$hasYield = $condResult->hasYield();
				$throwPoints = $condResult->getThrowPoints();
				$impurePoints = $condResult->getImpurePoints();
				$finalScope = $condResult->getFalseyScope();
			} else {
				$this->processExprNode($stmt, $stmt->cond, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep());
			}

			$breakExitPoints = $bodyScopeResult->getExitPointsByType(Break_::class);
			if (count($breakExitPoints) > 0) {
				$breakScope = $alwaysIterates ? null : $finalScope;
				foreach ($breakExitPoints as $breakExitPoint) {
					$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope());
				}
				$finalScope = $breakScope;
			}

			return new InternalStatementResult(
				$finalScope,
				$bodyScopeResult->hasYield() || $hasYield,
				$alwaysTerminating,
				$bodyScopeResult->getExitPointsForOuterLoop(),
				array_merge($throwPoints, $bodyScopeResult->getThrowPoints()),
				array_merge($impurePoints, $bodyScopeResult->getImpurePoints()),
			);
		} elseif ($stmt instanceof For_) {
			$initScope = $scope;
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			foreach ($stmt->init as $initExpr) {
				$initResult = $this->processExprNode($stmt, $initExpr, $initScope, $storage, $nodeCallback, ExpressionContext::createTopLevel());
				$initScope = $initResult->getScope();
				$hasYield = $hasYield || $initResult->hasYield();
				$throwPoints = array_merge($throwPoints, $initResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $initResult->getImpurePoints());
			}

			$originalStorage = $storage;

			$bodyScope = $initScope;
			$isIterableAtLeastOnce = TrinaryLogic::createYes();
			$lastCondExpr = array_last($stmt->cond);
			if (count($stmt->cond) > 0) {
				$storage = $originalStorage->duplicate();

				foreach ($stmt->cond as $condExpr) {
					$condResult = $this->processExprNode($stmt, $condExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep());
					$initScope = $condResult->getScope();

					// only the last condition expression is relevant whether the loop continues
					// see https://www.php.net/manual/en/control-structures.for.php
					if ($condExpr === $lastCondExpr) {
						$condTruthiness = ($this->treatPhpDocTypesAsCertain ? $condResult->getType() : $condResult->getNativeType())->toBoolean();
						$isIterableAtLeastOnce = $isIterableAtLeastOnce->and($condTruthiness->isTrue());
					}

					$hasYield = $hasYield || $condResult->hasYield();
					$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $condResult->getImpurePoints());
					$bodyScope = $condResult->getTruthyScope();
				}
			}

			if ($context->isTopLevel()) {
				$count = 0;
				do {
					$prevScope = $bodyScope;
					$storage = $originalStorage->duplicate();
					$bodyScope = $bodyScope->mergeWith($initScope);
					if ($lastCondExpr !== null) {
						$bodyScope = $this->processExprNode($stmt, $lastCondExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep())->getTruthyScope();
					}
					$bodyScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}

					foreach ($stmt->loop as $loopExpr) {
						$exprResult = $this->processExprNode($stmt, $loopExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createTopLevel());
						$bodyScope = $exprResult->getScope();
						$hasYield = $hasYield || $exprResult->hasYield();
						$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
						$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
					}

					if ($bodyScope->equals($prevScope)) {
						break;
					}

					if ($count >= self::GENERALIZE_AFTER_ITERATION) {
						$bodyScope = $prevScope->generalizeWith($bodyScope);
					}
					$count++;
				} while ($count < self::LOOP_SCOPE_ITERATIONS);
			}

			$storage = $originalStorage;
			$bodyScope = $bodyScope->mergeWith($initScope);

			$alwaysIterates = TrinaryLogic::createFromBoolean($context->isTopLevel());
			if ($lastCondExpr !== null) {
				$alwaysIterates = $alwaysIterates->and($bodyScope->getType($lastCondExpr)->toBoolean()->isTrue());
				$bodyScope = $this->processExprNode($stmt, $lastCondExpr, $bodyScope, $storage, $nodeCallback, ExpressionContext::createDeep())->getTruthyScope();
				$bodyScope = $this->inferForLoopExpressions($stmt, $lastCondExpr, $bodyScope);
			}

			$finalScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope();
			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
			}

			$loopScope = $finalScope;
			foreach ($stmt->loop as $loopExpr) {
				$loopScope = $this->processExprNode($stmt, $loopExpr, $loopScope, $storage, $nodeCallback, ExpressionContext::createTopLevel())->getScope();
			}
			$finalScope = $finalScope->generalizeWith($loopScope);

			if ($lastCondExpr !== null) {
				$finalScope = $finalScope->filterByFalseyValue($lastCondExpr);
			}

			$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
			if (count($breakExitPoints) > 0) {
				$breakScope = $alwaysIterates->yes() ? null : $finalScope;
				foreach ($breakExitPoints as $breakExitPoint) {
					$breakScope = $breakScope === null ? $breakExitPoint->getScope() : $breakScope->mergeWith($breakExitPoint->getScope());
				}
				$finalScope = $breakScope;
			}

			if ($isIterableAtLeastOnce->no() || $finalScopeResult->isAlwaysTerminating()) {
				if ($this->polluteScopeWithLoopInitialAssignments) {
					$finalScope = $initScope;
				} else {
					$finalScope = $scope;
				}

			} elseif ($isIterableAtLeastOnce->maybe()) {
				if ($this->polluteScopeWithLoopInitialAssignments) {
					$finalScope = $finalScope->mergeWith($initScope);
				} else {
					$finalScope = $finalScope->mergeWith($scope);
				}
			} else {
				if (!$this->polluteScopeWithLoopInitialAssignments) {
					$finalScope = $finalScope->mergeWith($scope);
				}
			}

			if ($alwaysIterates->yes()) {
				$isAlwaysTerminating = count($finalScopeResult->getExitPointsByType(Break_::class)) === 0;
			} elseif ($isIterableAtLeastOnce->yes()) {
				$isAlwaysTerminating = $finalScopeResult->isAlwaysTerminating();
			} else {
				$isAlwaysTerminating = false;
			}

			return new InternalStatementResult(
				$finalScope,
				$finalScopeResult->hasYield() || $hasYield,
				$isAlwaysTerminating,
				$finalScopeResult->getExitPointsForOuterLoop(),
				array_merge($throwPoints, $finalScopeResult->getThrowPoints()),
				array_merge($impurePoints, $finalScopeResult->getImpurePoints()),
			);
		} elseif ($stmt instanceof Switch_) {
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $condResult->getScope();
			$scopeForBranches = $scope;
			$finalScope = null;
			$prevScope = null;
			$hasDefaultCase = false;
			$alwaysTerminating = true;
			$hasYield = $condResult->hasYield();
			$exitPointsForOuterLoop = [];
			$throwPoints = $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			$fullCondExpr = null;
			foreach ($stmt->cases as $caseNode) {
				if ($caseNode->cond !== null) {
					$condExpr = new BinaryOp\Equal($stmt->cond, $caseNode->cond);
					$fullCondExpr = $fullCondExpr === null ? $condExpr : new BooleanOr($fullCondExpr, $condExpr);
					$caseResult = $this->processExprNode($stmt, $caseNode->cond, $scopeForBranches, $storage, $nodeCallback, ExpressionContext::createDeep());
					$scopeForBranches = $caseResult->getScope();
					$hasYield = $hasYield || $caseResult->hasYield();
					$throwPoints = array_merge($throwPoints, $caseResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $caseResult->getImpurePoints());
					$branchScope = $caseResult->getScope()->filterByTruthyValue($condExpr);
				} else {
					$hasDefaultCase = true;
					$fullCondExpr = null;
					$branchScope = $scopeForBranches;
				}

				$branchScope = $branchScope->mergeWith($prevScope);
				$branchScopeResult = $this->processStmtNodesInternal($caseNode, $caseNode->stmts, $branchScope, $storage, $nodeCallback, $context);
				$branchScope = $branchScopeResult->getScope();
				$branchFinalScopeResult = $branchScopeResult->filterOutLoopExitPoints();
				$hasYield = $hasYield || $branchFinalScopeResult->hasYield();
				foreach ($branchScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
					$alwaysTerminating = false;
					$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
				}
				foreach ($branchScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
				}
				$exitPointsForOuterLoop = array_merge($exitPointsForOuterLoop, $branchFinalScopeResult->getExitPointsForOuterLoop());
				$throwPoints = array_merge($throwPoints, $branchFinalScopeResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $branchFinalScopeResult->getImpurePoints());
				if ($branchScopeResult->isAlwaysTerminating()) {
					$alwaysTerminating = $alwaysTerminating && $branchFinalScopeResult->isAlwaysTerminating();
					$prevScope = null;
					if (isset($fullCondExpr)) {
						$scopeForBranches = $scopeForBranches->filterByFalseyValue($fullCondExpr);
						$fullCondExpr = null;
					}
					if (!$branchFinalScopeResult->isAlwaysTerminating()) {
						$finalScope = $branchScope->mergeWith($finalScope);
					}
				} else {
					$prevScope = $branchScope;
				}
			}

			$exhaustive = $scopeForBranches->getType($stmt->cond) instanceof NeverType;

			if (!$hasDefaultCase && !$exhaustive) {
				$alwaysTerminating = false;
			}

			if ($prevScope !== null && isset($branchFinalScopeResult)) {
				$finalScope = $prevScope->mergeWith($finalScope);
				$alwaysTerminating = $alwaysTerminating && $branchFinalScopeResult->isAlwaysTerminating();
			}

			if ((!$hasDefaultCase && !$exhaustive) || $finalScope === null) {
				$finalScope = $scopeForBranches->mergeWith($finalScope);
			}

			return new InternalStatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPointsForOuterLoop, $throwPoints, $impurePoints);
		} elseif ($stmt instanceof TryCatch) {
			$branchScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
			$branchScope = $branchScopeResult->getScope();
			$finalScope = $branchScopeResult->isAlwaysTerminating() ? null : $branchScope;

			$exitPoints = [];
			$finallyExitPoints = [];
			$alwaysTerminating = $branchScopeResult->isAlwaysTerminating();
			$hasYield = $branchScopeResult->hasYield();

			if ($stmt->finally !== null) {
				$finallyScope = $branchScope;
			} else {
				$finallyScope = null;
			}
			foreach ($branchScopeResult->getExitPoints() as $exitPoint) {
				$finallyExitPoints[] = $exitPoint->toPublic();
				if ($exitPoint->getStatement() instanceof Node\Stmt\Expression && $exitPoint->getStatement()->expr instanceof Expr\Throw_) {
					continue;
				}
				if ($finallyScope !== null) {
					$finallyScope = $finallyScope->mergeWith($exitPoint->getScope());
				}
				$exitPoints[] = $exitPoint;
			}

			$throwPoints = $branchScopeResult->getThrowPoints();
			$impurePoints = $branchScopeResult->getImpurePoints();
			$throwPointsForLater = [];
			$pastCatchTypes = new NeverType();

			foreach ($stmt->catches as $catchNode) {
				$this->callNodeCallback($nodeCallback, $catchNode, $scope, $storage);

				$originalCatchTypes = [];
				$catchTypes = [];
				foreach ($catchNode->types as $catchNodeType) {
					$catchType = new ObjectType($catchNodeType->toString());
					$originalCatchTypes[] = $catchType;
					$catchTypes[] = TypeCombinator::remove($catchType, $pastCatchTypes);
				}

				$originalCatchType = TypeCombinator::union(...$originalCatchTypes);
				$catchType = TypeCombinator::union(...$catchTypes);
				$pastCatchTypes = TypeCombinator::union($pastCatchTypes, $originalCatchType);

				$matchingThrowPoints = [];
				$matchingCatchTypes = array_fill_keys(array_keys($originalCatchTypes), false);

				// throwable matches all
				foreach ($originalCatchTypes as $catchTypeIndex => $catchTypeItem) {
					if (!$catchTypeItem->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
						continue;
					}

					foreach ($throwPoints as $throwPointIndex => $throwPoint) {
						$matchingThrowPoints[$throwPointIndex] = $throwPoint;
						$matchingCatchTypes[$catchTypeIndex] = true;
					}
				}

				// explicit only
				$onlyExplicitIsThrow = true;
				if (count($matchingThrowPoints) === 0) {
					foreach ($throwPoints as $throwPointIndex => $throwPoint) {
						foreach ($catchTypes as $catchTypeIndex => $catchTypeItem) {
							if ($catchTypeItem->isSuperTypeOf($throwPoint->getType())->no()) {
								continue;
							}

							$matchingCatchTypes[$catchTypeIndex] = true;
							if (!$throwPoint->isExplicit()) {
								continue;
							}
							$throwNode = $throwPoint->getNode();
							if (
								!$throwNode instanceof Expr\Throw_
								&& !($throwNode instanceof Node\Stmt\Expression && $throwNode->expr instanceof Expr\Throw_)
							) {
								$onlyExplicitIsThrow = false;
							}

							$matchingThrowPoints[$throwPointIndex] = $throwPoint;
						}
					}
				}

				// implicit only
				if (count($matchingThrowPoints) === 0 || $onlyExplicitIsThrow) {
					foreach ($throwPoints as $throwPointIndex => $throwPoint) {
						if ($throwPoint->isExplicit()) {
							continue;
						}

						foreach ($catchTypes as $catchTypeItem) {
							if ($catchTypeItem->isSuperTypeOf($throwPoint->getType())->no()) {
								continue;
							}

							$matchingThrowPoints[$throwPointIndex] = $throwPoint;
						}
					}
				}

				// include previously removed throw points
				if (count($matchingThrowPoints) === 0) {
					if ($originalCatchType->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
						foreach ($branchScopeResult->getThrowPoints() as $originalThrowPoint) {
							if (!$originalThrowPoint->canContainAnyThrowable()) {
								continue;
							}

							$matchingThrowPoints[] = $originalThrowPoint;
							$matchingCatchTypes = array_fill_keys(array_keys($originalCatchTypes), true);
						}
					}
				}

				// emit error
				foreach ($matchingCatchTypes as $catchTypeIndex => $matched) {
					if ($matched) {
						continue;
					}
					$this->callNodeCallback($nodeCallback, new CatchWithUnthrownExceptionNode($catchNode, $catchTypes[$catchTypeIndex], $originalCatchTypes[$catchTypeIndex]), $scope, $storage);
				}

				if (count($matchingThrowPoints) === 0) {
					continue;
				}

				// recompute throw points
				$newThrowPoints = [];
				foreach ($throwPoints as $throwPoint) {
					$newThrowPoint = $throwPoint->subtractCatchType($originalCatchType);

					if ($newThrowPoint->getType() instanceof NeverType) {
						continue;
					}

					$newThrowPoints[] = $newThrowPoint;
				}
				$throwPoints = $newThrowPoints;

				$catchScope = null;
				foreach ($matchingThrowPoints as $matchingThrowPoint) {
					if ($catchScope === null) {
						$catchScope = $matchingThrowPoint->getScope();
					} else {
						$catchScope = $catchScope->mergeWith($matchingThrowPoint->getScope());
					}
				}

				$variableName = null;
				if ($catchNode->var !== null) {
					if (!is_string($catchNode->var->name)) {
						throw new ShouldNotHappenException();
					}

					$variableName = $catchNode->var->name;
					$this->callNodeCallback($nodeCallback, new VariableAssignNode($catchNode->var, new TypeExpr($catchType)), $scope, $storage);
				}

				$catchScopeResult = $this->processStmtNodesInternal($catchNode, $catchNode->stmts, $catchScope->enterCatchType($catchType, $variableName), $storage, $nodeCallback, $context);
				$catchScopeForFinally = $catchScopeResult->getScope();

				$finalScope = $catchScopeResult->isAlwaysTerminating() ? $finalScope : $catchScopeResult->getScope()->mergeWith($finalScope);
				$alwaysTerminating = $alwaysTerminating && $catchScopeResult->isAlwaysTerminating();
				$hasYield = $hasYield || $catchScopeResult->hasYield();
				$catchThrowPoints = $catchScopeResult->getThrowPoints();
				$impurePoints = array_merge($impurePoints, $catchScopeResult->getImpurePoints());
				$throwPointsForLater = array_merge($throwPointsForLater, $catchThrowPoints);

				if ($finallyScope !== null) {
					$finallyScope = $finallyScope->mergeWith($catchScopeForFinally);
				}
				foreach ($catchScopeResult->getExitPoints() as $exitPoint) {
					$finallyExitPoints[] = $exitPoint->toPublic();
					if ($exitPoint->getStatement() instanceof Node\Stmt\Expression && $exitPoint->getStatement()->expr instanceof Expr\Throw_) {
						continue;
					}
					if ($finallyScope !== null) {
						$finallyScope = $finallyScope->mergeWith($exitPoint->getScope());
					}
					$exitPoints[] = $exitPoint;
				}

				foreach ($catchThrowPoints as $catchThrowPoint) {
					if ($finallyScope === null) {
						continue;
					}
					$finallyScope = $finallyScope->mergeWith($catchThrowPoint->getScope());
				}
			}

			if ($finalScope === null) {
				$finalScope = $scope;
			}

			foreach ($throwPoints as $throwPoint) {
				if ($finallyScope === null) {
					continue;
				}
				$finallyScope = $finallyScope->mergeWith($throwPoint->getScope());
			}

			if ($finallyScope !== null) {
				$originalFinallyScope = $finallyScope;
				$finallyResult = $this->processStmtNodesInternal($stmt->finally, $stmt->finally->stmts, $finallyScope, $storage, $nodeCallback, $context);
				$alwaysTerminating = $alwaysTerminating || $finallyResult->isAlwaysTerminating();
				$hasYield = $hasYield || $finallyResult->hasYield();
				$throwPointsForLater = array_merge($throwPointsForLater, $finallyResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $finallyResult->getImpurePoints());
				$finallyScope = $finallyResult->getScope();
				$finalScope = $finallyResult->isAlwaysTerminating() ? $finalScope : $finalScope->processFinallyScope($finallyScope, $originalFinallyScope);
				if (count($finallyResult->getExitPoints()) > 0 && $finallyResult->isAlwaysTerminating()) {
					$this->callNodeCallback($nodeCallback, new FinallyExitPointsNode(
						$finallyResult->toPublic()->getExitPoints(),
						$finallyExitPoints,
					), $scope, $storage);
				}
				$exitPoints = array_merge($exitPoints, $finallyResult->getExitPoints());
			}

			return new InternalStatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPoints, array_merge($throwPoints, $throwPointsForLater), $impurePoints);
		} elseif ($stmt instanceof Unset_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			foreach ($stmt->vars as $var) {
				$scope = $this->lookForSetAllowedUndefinedExpressions($scope, $var);
				$exprResult = $this->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $exprResult->getScope();
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $var);
				$hasYield = $hasYield || $exprResult->hasYield();
				$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
				if ($var instanceof ArrayDimFetch && $var->dim !== null) {
					$varType = $scope->getType($var->var);
					if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
						$throwPoints = array_merge($throwPoints, $this->processExprNode(
							$stmt,
							new MethodCall(new TypeExpr($varType), 'offsetUnset'),
							$scope,
							$storage,
							new NoopNodeCallback(),
							ExpressionContext::createDeep(),
						)->getThrowPoints());
					}

					$clonedVar = $this->deepNodeCloner->cloneNode($var->var);
					$traverser = new NodeTraverser();
					$traverser->addVisitor(new class () extends NodeVisitorAbstract {

						#[Override]
						public function leaveNode(Node $node): ?ExistingArrayDimFetch
						{
							if (!$node instanceof ArrayDimFetch || $node->dim === null) {
								return null;
							}

							return new ExistingArrayDimFetch($node->var, $node->dim);
						}

					});

					/** @var Expr $clonedVar */
					[$clonedVar] = $traverser->traverse([$clonedVar]);
					$scope = $this->processVirtualAssign($scope, $storage, $stmt, $clonedVar, new UnsetOffsetExpr($var->var, $var->dim), $nodeCallback)->getScope();
				} elseif ($var instanceof PropertyFetch) {
					$scope = $scope->invalidateExpression($var);
					$impurePoints[] = new ImpurePoint(
						$scope,
						$var,
						'propertyUnset',
						'property unset',
						true,
					);
				} else {
					$scope = $scope->invalidateExpression($var);
				}

				$scope = $scope->invalidateExpression(new ForeachValueByRefExpr($var));
			}
		} elseif ($stmt instanceof Node\Stmt\Use_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			foreach ($stmt->uses as $use) {
				$this->callNodeCallback($nodeCallback, $use, $scope, $storage);
			}
		} elseif ($stmt instanceof Node\Stmt\Global_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [
				new ImpurePoint(
					$scope,
					$stmt,
					'global',
					'global variable',
					true,
				),
			];
			$vars = [];
			foreach ($stmt->vars as $var) {
				if (!$var instanceof Variable) {
					throw new ShouldNotHappenException();
				}
				$scope = $this->lookForSetAllowedUndefinedExpressions($scope, $var);
				$varResult = $this->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $var);

				if (!is_string($var->name)) {
					continue;
				}

				$varType = $this->getGlobalVariableType($var->name);
				$scope = $scope->assignVariable($var->name, $varType, $varType, TrinaryLogic::createYes());
				$vars[] = $var->name;
			}
			$scope = $this->processVarAnnotation($scope, $vars, $stmt);
		} elseif ($stmt instanceof Static_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [
				new ImpurePoint(
					$scope,
					$stmt,
					'static',
					'static variable',
					true,
				),
			];

			$vars = [];
			foreach ($stmt->vars as $var) {
				if (!is_string($var->var->name)) {
					throw new ShouldNotHappenException();
				}

				if ($var->default !== null) {
					$defaultExprResult = $this->processExprNode($stmt, $var->default, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
					$impurePoints = array_merge($impurePoints, $defaultExprResult->getImpurePoints());
				}

				$scope = $scope->enterExpressionAssign($var->var);
				$varResult = $this->processExprNode($stmt, $var->var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
				$scope = $scope->exitExpressionAssign($var->var);

				$scope = $scope->assignVariable($var->var->name, new MixedType(), new MixedType(), TrinaryLogic::createYes());
				$vars[] = $var->var->name;
			}

			$scope = $this->processVarAnnotation($scope, $vars, $stmt);
		} elseif ($stmt instanceof Node\Stmt\Const_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			foreach ($stmt->consts as $const) {
				$this->callNodeCallback($nodeCallback, $const, $scope, $storage);
				$constResult = $this->processExprNode($stmt, $const->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$impurePoints = array_merge($impurePoints, $constResult->getImpurePoints());
				if ($const->namespacedName !== null) {
					$constantName = new Name\FullyQualified($const->namespacedName->toString());
				} else {
					$constantName = new Name\FullyQualified($const->name->toString());
				}
				$scope = $scope->assignExpression(new ConstFetch($constantName), $constResult->getType(), $constResult->getNativeType());
			}
		} elseif ($stmt instanceof Node\Stmt\ClassConst) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
			foreach ($stmt->consts as $const) {
				$this->callNodeCallback($nodeCallback, $const, $scope, $storage);
				$constResult = $this->processExprNode($stmt, $const->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$impurePoints = array_merge($impurePoints, $constResult->getImpurePoints());
				if ($scope->getClassReflection() === null) {
					throw new ShouldNotHappenException();
				}
				$scope = $scope->assignExpression(
					new Expr\ClassConstFetch(new Name\FullyQualified($scope->getClassReflection()->getName()), $const->name),
					$constResult->getType(),
					$constResult->getNativeType(),
				);
			}
		} elseif ($stmt instanceof Node\Stmt\EnumCase) {
			$hasYield = false;
			$throwPoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
			$impurePoints = [];
			if ($stmt->expr !== null) {
				$exprResult = $this->processExprNode($stmt, $stmt->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$impurePoints = $exprResult->getImpurePoints();
			}
		} elseif ($stmt instanceof InlineHTML) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [
				new ImpurePoint($scope, $stmt, 'betweenPhpTags', 'output between PHP opening and closing tags', true),
			];
		} elseif ($stmt instanceof Node\Stmt\Block) {
			$result = $this->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
			if ($this->polluteScopeWithBlock) {
				return $result;
			}

			return new InternalStatementResult(
				$scope->mergeWith($result->getScope()),
				$result->hasYield(),
				$result->isAlwaysTerminating(),
				$result->getExitPoints(),
				$result->getThrowPoints(),
				$result->getImpurePoints(),
				$result->getEndStatements(),
			);
		} elseif ($stmt instanceof Node\Stmt\Nop) {
			$hasYield = false;
			$throwPoints = $overridingThrowPoints ?? [];
			$impurePoints = [];
		} elseif ($stmt instanceof Node\Stmt\GroupUse) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->uses as $use) {
				$this->callNodeCallback($nodeCallback, $use, $scope, $storage);
			}
			$impurePoints = [];
		} else {
			$hasYield = false;
			$throwPoints = $overridingThrowPoints ?? [];
			$impurePoints = [];
		}

		return new InternalStatementResult($scope, $hasYield, false, [], $throwPoints, $impurePoints);
	}

	/**
	 * @return array{bool, string|null}
	 */
	private function getDeprecatedAttribute(Scope $scope, Node\Stmt\Function_|Node\Stmt\ClassMethod|Node\PropertyHook $stmt): array
	{
		$initializerExprContext = InitializerExprContext::fromStubParameter(
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->getFile(),
			$stmt,
		);
		$isDeprecated = false;
		$deprecatedDescription = null;
		$deprecatedDescriptionType = null;
		foreach ($stmt->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toString() !== 'Deprecated') {
					continue;
				}
				$isDeprecated = true;
				$arguments = $attr->args;
				foreach ($arguments as $i => $arg) {
					$argName = $arg->name;
					if ($argName === null) {
						if ($i !== 0) {
							continue;
						}

						$deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext);
						break;
					}

					if ($argName->toString() !== 'message') {
						continue;
					}

					$deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext);
					break;
				}
			}
		}

		if ($deprecatedDescriptionType !== null) {
			$constantStrings = $deprecatedDescriptionType->getConstantStrings();
			if (count($constantStrings) === 1) {
				$deprecatedDescription = $constantStrings[0]->getValue();
			}
		}

		return [$isDeprecated, $deprecatedDescription];
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

	private function getCurrentClassReflection(Node\Stmt\ClassLike $stmt, string $className, Scope $scope): ClassReflection
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return $this->createAstClassReflection($stmt, $className, $scope);
		}

		$defaultClassReflection = $this->reflectionProvider->getClass($className);
		if ($defaultClassReflection->getFileName() !== $scope->getFile()) {
			return $this->createAstClassReflection($stmt, $className, $scope);
		}

		$startLine = $defaultClassReflection->getNativeReflection()->getStartLine();
		if ($startLine !== $stmt->getStartLine()) {
			return $this->createAstClassReflection($stmt, $className, $scope);
		}

		return $defaultClassReflection;
	}

	private function createAstClassReflection(Node\Stmt\ClassLike $stmt, string $className, Scope $scope): ClassReflection
	{
		$nodeToReflection = new NodeToReflection();
		$betterReflectionClass = $nodeToReflection->__invoke(
			$this->reflector,
			$stmt,
			new LocatedSource(FileReader::read($scope->getFile()), $className, $scope->getFile()),
			$scope->getNamespace() !== null ? new Node\Stmt\Namespace_(new Name($scope->getNamespace())) : null,
		);
		if (!$betterReflectionClass instanceof \PHPStan\BetterReflection\Reflection\ReflectionClass) {
			throw new ShouldNotHappenException();
		}

		return $this->classReflectionFactory->create(
			$betterReflectionClass->getName(),
			$betterReflectionClass instanceof ReflectionEnum && PHP_VERSION_ID >= 80000
				? new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum($betterReflectionClass)
				: new ReflectionClass($betterReflectionClass),
			null,
			null,
			null,
			sprintf('%s:%d', $scope->getFile(), $stmt->getStartLine()),
		);
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

	private function findEarlyTerminatingExpr(Expr $expr, Scope $scope): ?Expr
	{
		if ($expr instanceof Expr\Exit_ || $expr instanceof Expr\Throw_) {
			return $expr;
		}

		$exprType = $scope->getType($expr);
		if ($exprType instanceof NeverType && $exprType->isExplicit()) {
			return $expr;
		}

		return null;
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

		$this->callNodeCallbackWithExpression($nodeCallback, $expr, $scope, $storage, $context);

		$exprHandler = ExprHandlerRegistry::resolve($expr, $this->container);
		if ($exprHandler !== null) {
			$expressionResult = $exprHandler->processExpr($this, $stmt, $expr, $scope, $storage, $nodeCallback, $context);
			$this->storeExpressionResult($storage, $expr, $expressionResult);
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
	 * @param 'get'|'set' $hookName
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPointsFromPropertyHook(
		MutatingScope $scope,
		PropertyFetch $propertyFetch,
		PhpPropertyReflection $propertyReflection,
		string $hookName,
	): array
	{
		$scopeFunction = $scope->getFunction();
		if (
			$scopeFunction instanceof PhpMethodFromParserNodeReflection
			&& $scopeFunction->isPropertyHook()
			&& $propertyFetch->var instanceof Variable
			&& $propertyFetch->var->name === 'this'
			&& $propertyFetch->name instanceof Identifier
			&& $propertyFetch->name->toString() === $scopeFunction->getHookedPropertyName()
		) {
			return [];
		}
		$declaringClass = $propertyReflection->getDeclaringClass();
		if (!$propertyReflection->hasHook($hookName)) {
			if (
				$propertyReflection->isPrivate()
				|| $propertyReflection->isFinal()->yes()
				|| $declaringClass->isFinal()
			) {
				return [];
			}

			if ($this->implicitThrows) {
				return [InternalThrowPoint::createImplicit($scope, $propertyFetch)];
			}

			return [];
		}

		$getHook = $propertyReflection->getHook($hookName);
		$throwType = $getHook->getThrowType();

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return [InternalThrowPoint::createExplicit($scope, $throwType, $propertyFetch, true)];
			}
		} elseif ($this->implicitThrows) {
			return [InternalThrowPoint::createImplicit($scope, $propertyFetch)];
		}

		return [];
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
		Expr $expr,
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
		$nodeCallback($node, $scope);
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
		$gatheredYieldStatements = [];
		$closureImpurePoints = [];
		$invalidateExpressions = [];
		$closureStmtsCallback = new GatheringNodeCallback(static function (Node $node, Scope $scope) use (&$executionEnds, &$gatheredReturnStatements, &$gatheredYieldStatements, &$closureScope, &$closureImpurePoints, &$invalidateExpressions): void {
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
			}
			if (!$node instanceof Return_) {
				return;
			}

			$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
		}, $nodeCallback);

		if (count($byRefUses) === 0) {
			$statementResult = $this->processStmtNodesInternalWithoutFlushingPendingFibers($expr, $expr->stmts, $closureScope, $storage, $closureStmtsCallback, StatementContext::createTopLevel());
			$publicStatementResult = $statementResult->toPublic();
			$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$publicStatementResult,
				$executionEnds,
				array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
			), $closureScope, $storage);

			return new ProcessClosureResult($scope, $statementResult->getThrowPoints(), $statementResult->getImpurePoints(), $invalidateExpressions);
		}

		$originalStorage = $storage;

		$count = 0;
		$closureResultScope = null;
		do {
			$prevScope = $closureScope;

			$storage = $originalStorage->duplicate();
			$intermediaryClosureScopeResult = $this->processStmtNodesInternalWithoutFlushingPendingFibers($expr, $expr->stmts, $closureScope, $storage, new NoopNodeCallback(), StatementContext::createTopLevel());
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
		$statementResult = $this->processStmtNodesInternalWithoutFlushingPendingFibers($expr, $expr->stmts, $closureScope, $storage, $closureStmtsCallback, StatementContext::createTopLevel());
		$publicStatementResult = $statementResult->toPublic();
		$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredYieldStatements,
			$publicStatementResult,
			$executionEnds,
			array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
		), $closureScope, $storage);

		return new ProcessClosureResult($scope, $statementResult->getThrowPoints(), $statementResult->getImpurePoints(), $invalidateExpressions, $closureResultScope, $byRefUses);
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
	): ExpressionResult
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
		$arrowFunctionType = $arrowFunctionScope->getAnonymousFunctionReflection();
		if ($arrowFunctionType === null) {
			throw new ShouldNotHappenException();
		}
		$this->callNodeCallback($nodeCallback, new InArrowFunctionNode($arrowFunctionType, $expr), $arrowFunctionScope, $storage);
		$exprResult = $this->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $storage, $nodeCallback, ExpressionContext::createTopLevel());

		return $this->expressionResultFactory->create($scope, beforeScope: $scope, expr: $expr, hasYield: false, isAlwaysTerminating: $exprResult->isAlwaysTerminating(), throwPoints: $exprResult->getThrowPoints(), impurePoints: $exprResult->getImpurePoints());
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
	private function processParamNode(
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
	private function processAttributeGroups(
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
						$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
							$scope,
							$attr->args,
							$constructorReflection->getVariants(),
							$constructorReflection->getNamedArgumentsVariants(),
						);
						$expr = new New_($attr->name, $attr->args);
						$expr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
						$this->processArgs($stmt, $constructorReflection, null, $parametersAcceptor, $expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
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
	 * @param Node\PropertyHook[] $hooks
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processPropertyHooks(
		Node\Stmt $stmt,
		Identifier|Name|ComplexType|null $nativeTypeNode,
		?Type $phpDocType,
		string $propertyName,
		array $hooks,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
	): void
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();

		foreach ($hooks as $hook) {
			$this->callNodeCallback($nodeCallback, $hook, $scope, $storage);
			$this->processAttributeGroups($stmt, $hook->attrGroups, $scope, $storage, $nodeCallback);

			[, $phpDocParameterTypes,,,, $phpDocThrowType,,,,,,,, $phpDocComment,,,,,, $resolvedPhpDoc] = $this->getPhpDocs($scope, $hook);

			foreach ($hook->params as $param) {
				$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
			}

			[$isDeprecated, $deprecatedDescription] = $this->getDeprecatedAttribute($scope, $hook);

			$hookScope = $scope->enterPropertyHook(
				$hook,
				$propertyName,
				$nativeTypeNode,
				$phpDocType,
				$phpDocParameterTypes,
				$phpDocThrowType,
				$deprecatedDescription,
				$isDeprecated,
				$phpDocComment,
				$resolvedPhpDoc,
			);
			$hookReflection = $hookScope->getFunction();
			if (!$hookReflection instanceof PhpMethodFromParserNodeReflection) {
				throw new ShouldNotHappenException();
			}

			if (!$classReflection->hasNativeProperty($propertyName)) {
				throw new ShouldNotHappenException();
			}

			$propertyReflection = $classReflection->getNativeProperty($propertyName);

			$this->callNodeCallback($nodeCallback, new InPropertyHookNode(
				$classReflection,
				$hookReflection,
				$propertyReflection,
				$hook,
			), $hookScope, $storage);

			$stmts = $hook->getStmts();
			if ($stmts === null) {
				return;
			}

			if ($hook->body instanceof Expr) {
				// enrich attributes of nodes in short hook body statements
				$traverser = new NodeTraverser(
					new LineAttributesVisitor($hook->body->getStartLine(), $hook->body->getEndLine()),
				);
				$traverser->traverse($stmts);
			}

			$gatheredReturnStatements = [];
			$executionEnds = [];
			$methodImpurePoints = [];
			$statementResult = $this->processStmtNodesInternal(new PropertyHookStatementNode($hook), $stmts, $hookScope, $storage, new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($hookScope, &$gatheredReturnStatements, &$executionEnds, &$hookImpurePoints): void {
				if ($scope->getFunction() !== $hookScope->getFunction()) {
					return;
				}
				if ($scope->isInAnonymousFunction()) {
					return;
				}
				if ($node instanceof PropertyAssignNode) {
					$hookImpurePoints[] = new ImpurePoint(
						$scope,
						$node,
						'propertyAssign',
						'property assignment',
						true,
					);
					return;
				}
				if ($node instanceof ExecutionEndNode) {
					$executionEnds[] = $node;
					return;
				}
				if (!$node instanceof Return_) {
					return;
				}

				$gatheredReturnStatements[] = new ReturnStatement($scope, $node);
			}, $nodeCallback), StatementContext::createTopLevel())->toPublic();

			$this->callNodeCallback($nodeCallback, new PropertyHookReturnStatementsNode(
				$hook,
				$gatheredReturnStatements,
				$statementResult,
				$executionEnds,
				array_merge($statementResult->getImpurePoints(), $methodImpurePoints),
				$classReflection,
				$hookReflection,
				$propertyReflection,
			), $hookScope, $storage);
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
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processArgs(
		Node\Stmt $stmt,
		$calleeReflection,
		?ExtendedMethodReflection $nakedMethodReflection,
		?ParametersAcceptor $parametersAcceptor,
		CallLike $callLike,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?MutatingScope $closureBindScope = null,
	): ExpressionResult
	{
		$args = $callLike->getArgs();

		$parameters = null;
		if ($parametersAcceptor !== null) {
			$parameters = $parametersAcceptor->getParameters();
		}

		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		/** @var list<array{InvalidateExprNode[], string[]}> $deferredInvalidateExpressions */
		$deferredInvalidateExpressions = [];
		/** @var ProcessClosureResult[] $deferredByRefClosureResults */
		$deferredByRefClosureResults = [];

		$processingOrder = array_keys($args);
		$hasReorderedArgs = false;
		foreach ($args as $arg) {
			if ($arg->hasAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE)) {
				$hasReorderedArgs = true;
				break;
			}
		}
		if ($hasReorderedArgs) {
			usort($processingOrder, static function (int $a, int $b) use ($args): int {
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
		}

		foreach ($processingOrder as $i) {
			$arg = $args[$i];
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
				} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
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
			if ($i === 0 && $closureBindScope !== null && ($arg->value instanceof Expr\Closure || $arg->value instanceof Expr\ArrowFunction)) {
				$scopeToPass = $closureBindScope;
			}

			if ($arg->value instanceof Expr\Closure) {
				$restoreThisScope = null;
				if (
					$closureBindScope === null
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
					}
				}

				$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
				$closureResult = $this->processClosureNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context, $parameterType, $parameterNativeType);
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
					$closureBindScope === null
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
					}
				}

				$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
				$arrowFunctionResult = $this->processArrowFunctionNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $parameterType, $parameterNativeType);
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
			}

			if ($assignByReference && $lookForUnset) {
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $arg->value);
			}

			if ($calleeReflection !== null) {
				$scope = $scope->popInFunctionCall();
			}

			if ($i !== 0 || $closureBindScope === null) {
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

		if ($parameters !== null) {
			foreach ($args as $i => $arg) {
				$assignByReference = false;
				$currentParameter = null;
				if (isset($parameters[$i])) {
					$currentParameter = $parameters[$i];
				} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
					$currentParameter = array_last($parameters);
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
							$nakedParametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
								$scope,
								$args,
								$nakedMethodReflection->getVariants(),
								$nakedMethodReflection->getNamedArgumentsVariants(),
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
		return $this->expressionResultFactory->create($scope, $scope, $callLike, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
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
		return $this->container->getByType(AssignHandler::class)->processAssignVar(
			$this,
			$scope,
			$storage,
			$stmt,
			$var,
			$assignedExpr,
			new VirtualAssignNodeCallback($nodeCallback),
			ExpressionContext::createDeep(),
			fn (MutatingScope $scope): ExpressionResult => $this->expressionResultFactory->create($scope, beforeScope: $scope, expr: $assignedExpr, hasYield: false, isAlwaysTerminating: false, throwPoints: [], impurePoints: []),
			false,
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
	 * @param array<int, string> $variableNames
	 */
	public function processVarAnnotation(MutatingScope $scope, array $variableNames, Node\Stmt $node, bool &$changed = false): MutatingScope
	{
		$function = $scope->getFunction();
		$varTags = [];
		foreach ($node->getComments() as $comment) {
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
			foreach ($resolvedPhpDoc->getVarTags() as $key => $varTag) {
				$varTags[$key] = $varTag;
			}
		}

		if (count($varTags) === 0) {
			return $scope;
		}

		foreach ($variableNames as $variableName) {
			if (!isset($varTags[$variableName])) {
				continue;
			}

			$variableType = $varTags[$variableName]->getType();
			$changed = true;
			$scope = $scope->assignVariable($variableName, $variableType, new MixedType(), TrinaryLogic::createYes());
		}

		if (count($variableNames) === 1 && count($varTags) === 1 && isset($varTags[0])) {
			$variableType = $varTags[0]->getType();
			$changed = true;
			$scope = $scope->assignVariable($variableNames[0], $variableType, new MixedType(), TrinaryLogic::createYes());
		}

		return $scope;
	}

	/**
	 * @return array{bodyScope: MutatingScope, endScope: MutatingScope, totalKeys: int}|null
	 */
	private function tryProcessUnrolledConstantArrayForeach(
		Foreach_ $stmt,
		MutatingScope $originalScope,
		ExpressionResultStorage $originalStorage,
		StatementContext $context,
	): ?array
	{
		if ($stmt->byRef) {
			return null;
		}
		if (!($stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name))) {
			return null;
		}
		if ($stmt->keyVar !== null && !($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name))) {
			return null;
		}

		$iterateeType = $originalScope->getType($stmt->expr);
		if (!$iterateeType->isConstantArray()->yes()) {
			return null;
		}
		$constantArrays = $iterateeType->getConstantArrays();
		if (count($constantArrays) === 0) {
			return null;
		}

		$totalKeys = 0;
		$hasUnsealed = false;
		foreach ($constantArrays as $constantArray) {
			$totalKeys += count($constantArray->getKeyTypes());
			if (!$constantArray->isUnsealed()->yes()) {
				continue;
			}
			$hasUnsealed = true;
		}
		if ($totalKeys === 0 || $totalKeys > self::FOREACH_UNROLL_LIMIT) {
			return null;
		}
		$foreachUnrollFactor = $context->getForeachUnrollFactor();
		if ($foreachUnrollFactor > 1 && $foreachUnrollFactor * $totalKeys > self::FOREACH_UNROLL_NESTED_LIMIT) {
			return null;
		}

		$nativeIterateeType = $originalScope->getNativeType($stmt->expr);
		$nativeConstantArrays = $nativeIterateeType->getConstantArrays();
		$matchedNativeArrays = count($nativeConstantArrays) === count($constantArrays) ? $nativeConstantArrays : null;

		$valueVarName = $stmt->valueVar->name;
		$keyVarName = $stmt->keyVar instanceof Variable ? $stmt->keyVar->name : null;

		$allBodyScopes = [];
		$allChainScopes = [];
		$allBreakScopes = [];

		$bodyContext = $context->enterUnrolledForeach($totalKeys);

		foreach ($constantArrays as $arrayIndex => $constantArray) {
			$keyTypes = $constantArray->getKeyTypes();
			$valueTypes = $constantArray->getValueTypes();
			if (count($keyTypes) === 0) {
				continue;
			}

			$nativeConstantArray = $matchedNativeArrays !== null ? $matchedNativeArrays[$arrayIndex] : null;
			$optionalKeys = array_fill_keys($constantArray->getOptionalKeys(), true);

			$chainScope = $originalScope;
			$entryScopes = [];

			foreach ($keyTypes as $i => $keyType) {
				$valueType = $valueTypes[$i];
				$isOptional = isset($optionalKeys[$i]);

				$nativeKeyType = $nativeConstantArray !== null && isset($nativeConstantArray->getKeyTypes()[$i])
					? $nativeConstantArray->getKeyTypes()[$i]
					: $keyType;
				$nativeValueType = $nativeConstantArray !== null && isset($nativeConstantArray->getValueTypes()[$i])
					? $nativeConstantArray->getValueTypes()[$i]
					: $valueType;

				$iterScope = $chainScope->assignVariable(
					$valueVarName,
					$valueType,
					$nativeValueType,
					TrinaryLogic::createYes(),
				);
				$iterScope = $iterScope->assignExpression(
					new OriginalForeachValueExpr($valueVarName),
					$valueType,
					$nativeValueType,
				);
				if ($keyVarName !== null) {
					$iterScope = $iterScope->assignVariable(
						$keyVarName,
						$keyType,
						$nativeKeyType,
						TrinaryLogic::createYes(),
					);
					$iterScope = $iterScope->assignExpression(
						new OriginalForeachKeyExpr($keyVarName),
						$keyType,
						$nativeKeyType,
					);
					$iterScope = $iterScope->assignExpression(
						new ArrayDimFetch($stmt->expr, $stmt->keyVar),
						$valueType,
						$nativeValueType,
					);
				}

				$entryScopes[] = $iterScope;

				$iterStorage = $originalStorage->duplicate();
				$bodyResult = $this->processStmtNodesInternal(
					$stmt,
					$stmt->stmts,
					$iterScope,
					$iterStorage,
					new NoopNodeCallback(),
					$bodyContext,
				)->filterOutLoopExitPoints();

				$iterEndScope = $bodyResult->getScope();
				foreach ($bodyResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$iterEndScope = $iterEndScope->mergeWith($continueExitPoint->getScope());
				}
				foreach ($bodyResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
					$allBreakScopes[] = $breakExitPoint->getScope();
				}

				if ($isOptional) {
					$chainScope = $iterEndScope->mergeWith($chainScope);
				} else {
					$chainScope = $iterEndScope;
				}
			}

			$arrayBodyScope = $entryScopes[0];
			for ($i = 1, $c = count($entryScopes); $i < $c; $i++) {
				$arrayBodyScope = $arrayBodyScope->mergeWith($entryScopes[$i]);
			}
			if (count($entryScopes) === 1) {
				$arrayBodyScope = $arrayBodyScope->mergeWith($chainScope);
			}

			$allBodyScopes[] = $arrayBodyScope;
			$allChainScopes[] = $chainScope;
		}

		if ($allBodyScopes === []) {
			return null;
		}

		$bodyScope = $allBodyScopes[0];
		for ($i = 1, $c = count($allBodyScopes); $i < $c; $i++) {
			$bodyScope = $bodyScope->mergeWith($allBodyScopes[$i]);
		}

		$endScope = $allChainScopes[0];
		for ($i = 1, $c = count($allChainScopes); $i < $c; $i++) {
			$endScope = $endScope->mergeWith($allChainScopes[$i]);
		}

		foreach ($allBreakScopes as $breakScope) {
			$endScope = $endScope->mergeWith($breakScope);
		}

		// Unsealed shapes describe zero-or-more additional entries beyond the
		// explicit keys. Run the scope-generalizing loop on top of the
		// unrolled explicit iterations so body-scope variables (e.g. counters)
		// account for the extra iterations while keeping the lower bound
		// established by the non-optional explicit keys.
		if ($hasUnsealed) {
			$loopScope = $endScope;
			$count = 0;
			do {
				$prevLoopScope = $loopScope;
				$iterStorage = $originalStorage->duplicate();
				$iterBodyScope = $loopScope->mergeWith($endScope);
				$iterBodyScope = $this->enterForeach($iterBodyScope, $iterStorage, $originalScope, $stmt, new NoopNodeCallback());
				$iterBodyScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $iterBodyScope, $iterStorage, new NoopNodeCallback(), $context->enterDeep())->filterOutLoopExitPoints();
				$loopScope = $iterBodyScopeResult->getScope();
				foreach ($iterBodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$loopScope = $loopScope->mergeWith($continueExitPoint->getScope());
				}
				foreach ($iterBodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
					$endScope = $endScope->mergeWith($breakExitPoint->getScope());
				}
				$bodyScope = $bodyScope->mergeWith($loopScope);
				if ($loopScope->equals($prevLoopScope)) {
					break;
				}
				if ($count >= self::GENERALIZE_AFTER_ITERATION) {
					$loopScope = $prevLoopScope->generalizeWith($loopScope);
				}
				$count++;
			} while ($count < self::LOOP_SCOPE_ITERATIONS);

			$endScope = $endScope->mergeWith($loopScope);
		}

		return ['bodyScope' => $bodyScope, 'endScope' => $endScope, 'totalKeys' => $totalKeys];
	}

	private function getTraversableForeachThrowPoint(MutatingScope $scope, Expr $iteratee): ?InternalThrowPoint
	{
		$exprType = $scope->getType($iteratee);
		$traversableType = new ObjectType(Traversable::class);

		if ($traversableType->isSuperTypeOf($exprType)->no()) {
			return null;
		}

		$traversablePart = TypeCombinator::intersect($exprType, $traversableType);
		$iteratorAggregateType = new ObjectType(IteratorAggregate::class);

		if ($iteratorAggregateType->isSuperTypeOf($traversablePart)->yes()
			&& $traversablePart->hasMethod('getIterator')->yes()) {
			$method = $traversablePart->getMethod('getIterator', $scope);
			$throwType = $method->getThrowType();
			if ($throwType !== null) {
				if ($throwType->isVoid()->yes()) {
					return null;
				}
				return InternalThrowPoint::createExplicit($scope, $throwType, $iteratee, true);
			}

			if (!$this->implicitThrows) {
				return null;
			}
		}

		return InternalThrowPoint::createImplicit($scope, $iteratee);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function enterForeach(MutatingScope $scope, ExpressionResultStorage $storage, MutatingScope $originalScope, Foreach_ $stmt, callable $nodeCallback): MutatingScope
	{
		if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
			$scope = $this->processVarAnnotation($scope, [$stmt->expr->name], $stmt);
		}

		$iterateeType = $originalScope->getType($stmt->expr);
		if (
			($stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name))
			&& ($stmt->keyVar === null || ($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)))
		) {
			$keyVarName = $stmt->keyVar instanceof Variable ? $stmt->keyVar->name : null;
			$scope = $scope->enterForeach(
				$originalScope,
				$stmt->expr,
				$stmt->valueVar->name,
				$keyVarName,
				$stmt->byRef,
			);
			$vars = [$stmt->valueVar->name];
			if ($keyVarName !== null) {
				$vars[] = $keyVarName;
			}
		} else {
			$scope = $this->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$stmt->valueVar,
				new NativeTypeExpr(
					$originalScope->getIterableValueType($iterateeType),
					$originalScope->getIterableValueType($originalScope->getNativeType($stmt->expr)),
				),
				$nodeCallback,
			)->getScope();
			$vars = $this->getAssignedVariables($stmt->valueVar);
			if (
				$stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)
			) {
				$scope = $scope->enterForeachKey($originalScope, $stmt->expr, $stmt->keyVar->name);
				$vars[] = $stmt->keyVar->name;
			} elseif ($stmt->keyVar !== null) {
				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$stmt->keyVar,
					new NativeTypeExpr(
						$originalScope->getIterableKeyType($iterateeType),
						$originalScope->getIterableKeyType($originalScope->getNativeType($stmt->expr)),
					),
					$nodeCallback,
				)->getScope();
				$vars = array_merge($vars, $this->getAssignedVariables($stmt->keyVar));
			}

			if ($stmt->valueVar instanceof List_) {
				$scope = $this->addDestructureTaggedUnionConditionalHolders(
					$scope,
					$originalScope->getIterableValueType($iterateeType),
					$stmt->valueVar,
				);
			}
		}

		$constantArrays = $iterateeType->getConstantArrays();
		if (
			$stmt->getDocComment() === null
			&& $iterateeType->isConstantArray()->yes()
			&& count($constantArrays) === 1
			&& $stmt->valueVar instanceof Variable && is_string($stmt->valueVar->name)
			&& $stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)
		) {
			$valueConditionalHolders = [];
			$arrayDimFetchConditionalHolders = [];
			foreach ($constantArrays[0]->getKeyTypes() as $i => $keyType) {
				$valueType = $constantArrays[0]->getValueTypes()[$i];
				$keyExpressionTypeHolder = ExpressionTypeHolder::createYes(new Variable($stmt->keyVar->name), $keyType);

				$holder = new ConditionalExpressionHolder([
					'$' . $stmt->keyVar->name => $keyExpressionTypeHolder,
				], ExpressionTypeHolder::createYes($stmt->valueVar, $valueType));
				$valueConditionalHolders[$holder->getKey()] = $holder;
				$arrayDimFetchHolder = new ConditionalExpressionHolder([
					'$' . $stmt->keyVar->name => $keyExpressionTypeHolder,
				], ExpressionTypeHolder::createYes(new ArrayDimFetch($stmt->expr, $stmt->keyVar), $valueType));
				$arrayDimFetchConditionalHolders[$arrayDimFetchHolder->getKey()] = $arrayDimFetchHolder;
			}

			$scope = $scope->addConditionalExpressions(
				'$' . $stmt->valueVar->name,
				$valueConditionalHolders,
			);
			if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
				$scope = $scope->addConditionalExpressions(
					sprintf('$%s[$%s]', $stmt->expr->name, $stmt->keyVar->name),
					$arrayDimFetchConditionalHolders,
				);
			}
		}

		if (
			$stmt->expr instanceof FuncCall
			&& $stmt->expr->name instanceof Name
			&& !$stmt->expr->isFirstClassCallable()
			&& $stmt->expr->name->toLowerString() === 'array_keys'
			&& $stmt->valueVar instanceof Variable
		) {
			$args = $stmt->expr->getArgs();
			if (count($args) >= 1) {
				$arrayArg = $args[0]->value;
				$scope = $scope->assignExpression(
					new ArrayDimFetch($arrayArg, $stmt->valueVar),
					$scope->getType($arrayArg)->getIterableValueType(),
					$scope->getNativeType($arrayArg)->getIterableValueType(),
				);
			}
		}

		return $this->processVarAnnotation($scope, $vars, $stmt);
	}

	/**
	 * When destructuring an iterable whose value type is a tagged union of
	 * constant arrays — e.g. `array<array{null, int}|array{int, null}>` — the
	 * variants describe a relationship between the destructured variables that
	 * a per-variable narrowing would normally lose: knowing `$x === null` should
	 * imply `$y === int`, but `foreach ($a as [$x, $y])` assigns `$x` and `$y`
	 * independently, so each ends up as the union (`int|null`) and the link is
	 * dropped.
	 *
	 * Recover the link by storing conditional-expression holders on each
	 * destructured variable: for every variant, "when this variable matches the
	 * variant's value at its position, the other variables match the variant's
	 * values at their positions". A later `if ($x === null)` then fires the
	 * matching holder and narrows `$y` accordingly.
	 *
	 * Only handles flat positional / keyed destructure patterns (List_) where
	 * each item's target is a plain Variable; nested destructure is left for
	 * the regular per-variable type tracking.
	 */
	private function addDestructureTaggedUnionConditionalHolders(
		MutatingScope $scope,
		Type $iterableValueType,
		List_ $list,
	): MutatingScope
	{
		$constantArrays = $iterableValueType->getConstantArrays();
		if (count($constantArrays) < 2) {
			return $scope;
		}

		// Collect each list item's array-key value and target variable.
		$items = [];
		foreach ($list->items as $position => $item) {
			if ($item === null) {
				continue;
			}
			if (!$item->value instanceof Variable || !is_string($item->value->name)) {
				return $scope;
			}
			if ($item->key === null) {
				$keyValue = $position;
			} elseif ($item->key instanceof Node\Scalar\String_) {
				$keyValue = $item->key->value;
			} elseif ($item->key instanceof Node\Scalar\Int_) {
				$keyValue = $item->key->value;
			} else {
				return $scope;
			}
			$items[] = ['key' => $keyValue, 'name' => $item->value->name];
		}

		if (count($items) < 2) {
			return $scope;
		}

		// For every variant, every item must have a matching key with a single
		// value type at it; otherwise the variants don't all describe the same
		// destructure shape and we can't form a sound holder set.
		$variantValuesByItem = [];
		foreach ($items as $itemIdx => $itemInfo) {
			$variantValuesByItem[$itemIdx] = [];
			foreach ($constantArrays as $variantIdx => $variant) {
				$keyType = is_int($itemInfo['key']) ? new ConstantIntegerType($itemInfo['key']) : new ConstantStringType($itemInfo['key']);
				if (!$variant->hasOffsetValueType($keyType)->yes()) {
					return $scope;
				}
				$variantValuesByItem[$itemIdx][$variantIdx] = $variant->getOffsetValueType($keyType);
			}
		}

		// For each item × variant, build a holder: "when item is variant's value
		// at this position, the *other* items are the variant's values at their
		// positions". Skip the variant if the condition value is too wide to be
		// a useful discriminator (i.e. equal to the union of all the variant
		// values at this position — narrowing it back wouldn't pick a variant).
		foreach ($items as $itemIdx => $itemInfo) {
			$exprString = '$' . $itemInfo['name'];
			$variantConditionTypes = $variantValuesByItem[$itemIdx];
			$itemUnionType = TypeCombinator::union(...array_values($variantConditionTypes));
			$holders = [];
			foreach (array_keys($constantArrays) as $variantIdx) {
				$conditionType = $variantConditionTypes[$variantIdx];
				if ($conditionType->equals($itemUnionType)) {
					continue;
				}
				$conditions = [
					$exprString => ExpressionTypeHolder::createYes(new Variable($itemInfo['name']), $conditionType),
				];
				foreach ($items as $otherIdx => $otherInfo) {
					if ($otherIdx === $itemIdx) {
						continue;
					}
					$otherType = $variantValuesByItem[$otherIdx][$variantIdx];
					$holder = new ConditionalExpressionHolder(
						$conditions,
						ExpressionTypeHolder::createYes(new Variable($otherInfo['name']), $otherType),
					);
					$holders['$' . $otherInfo['name']][$holder->getKey()] = $holder;
				}
			}

			foreach ($holders as $targetExprString => $targetHolders) {
				$scope = $scope->addConditionalExpressions($targetExprString, $targetHolders);
			}
		}

		return $scope;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processTraitUse(Node\Stmt\TraitUse $node, MutatingScope $classScope, ExpressionResultStorage $storage, callable $nodeCallback): void
	{
		$parentTraitNames = [];
		$parent = $classScope->getParentScope();
		while ($parent !== null) {
			if ($parent->isInTrait()) {
				$parentTraitNames[] = $parent->getTraitReflection()->getName();
			}
			$parent = $parent->getParentScope();
		}

		foreach ($node->traits as $trait) {
			$traitName = (string) $trait;
			if (in_array($traitName, $parentTraitNames, true)) {
				continue;
			}
			if (!$this->reflectionProvider->hasClass($traitName)) {
				continue;
			}
			$traitReflection = $this->reflectionProvider->getClass($traitName);
			$traitFileName = $traitReflection->getFileName();
			if ($traitFileName === null) {
				continue; // trait from eval or from PHP itself
			}
			$fileName = $this->fileHelper->normalizePath($traitFileName);
			if (!isset($this->analysedFiles[$fileName])) {
				continue;
			}
			$adaptations = [];
			foreach ($node->adaptations as $adaptation) {
				if ($adaptation->trait === null) {
					$adaptations[] = $adaptation;
					continue;
				}
				if ($adaptation->trait->toLowerString() !== $trait->toLowerString()) {
					continue;
				}

				$adaptations[] = $adaptation;
			}
			$parserNodes = $this->parser->parseFile($fileName);
			$this->processNodesForTraitUse($parserNodes, $traitReflection, $classScope, $storage, $adaptations, $nodeCallback);
		}
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @param Node\Stmt\TraitUseAdaptation[] $adaptations
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processNodesForTraitUse($node, ClassReflection $traitReflection, MutatingScope $scope, ExpressionResultStorage $storage, array $adaptations, callable $nodeCallback): void
	{
		if ($node instanceof Node) {
			if ($node instanceof Node\Stmt\Trait_ && $traitReflection->getName() === (string) $node->namespacedName && $traitReflection->getNativeReflection()->getStartLine() === $node->getStartLine()) {
				$methodModifiers = [];
				$methodNames = [];
				foreach ($adaptations as $adaptation) {
					if (!$adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
						continue;
					}

					$methodName = $adaptation->method->toLowerString();
					if ($adaptation->newModifier !== null) {
						$methodModifiers[$methodName] = $adaptation->newModifier;
					}

					if ($adaptation->newName === null) {
						continue;
					}

					$methodNames[$methodName] = $adaptation->newName;
				}

				$stmts = $node->stmts;
				foreach ($stmts as $i => $stmt) {
					if (!$stmt instanceof Node\Stmt\ClassMethod) {
						continue;
					}
					$methodName = $stmt->name->toLowerString();
					$methodAst = clone $stmt;
					$stmts[$i] = $methodAst;
					if (array_key_exists($methodName, $methodModifiers)) {
						$methodAst->flags = ($methodAst->flags & ~ Modifiers::VISIBILITY_MASK) | $methodModifiers[$methodName];
					}

					if (!array_key_exists($methodName, $methodNames)) {
						continue;
					}

					$methodAst->setAttribute('originalTraitMethodName', $methodAst->name->toLowerString());
					$methodAst->name = $methodNames[$methodName];
				}

				if (!$scope->isInClass()) {
					throw new ShouldNotHappenException();
				}
				$traitScope = $scope->enterTrait($traitReflection);

				// attribute args are not processed as part of the trait statements
				// but rules like TraitAttributesRule ask about their types
				$this->processAttributeGroups($node, $node->attrGroups, $traitScope, $storage, new NoopNodeCallback());

				$this->callNodeCallback($nodeCallback, new InTraitNode($node, $traitReflection, $scope->getClassReflection()), $traitScope, $storage);
				$this->processStmtNodesInternal($node, $stmts, $traitScope, $storage, $nodeCallback, StatementContext::createTopLevel());
				return;
			}
			if ($node instanceof Node\Stmt\ClassLike) {
				return;
			}
			if ($node instanceof Node\FunctionLike) {
				return;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->{$subNodeName};
				$this->processNodesForTraitUse($subNode, $traitReflection, $scope, $storage, $adaptations, $nodeCallback);
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodesForTraitUse($subNode, $traitReflection, $scope, $storage, $adaptations, $nodeCallback);
			}
		}
	}

	public function processCalledMethod(MethodReflection $methodReflection): ?MutatingScope
	{
		$declaringClass = $methodReflection->getDeclaringClass();
		if ($declaringClass->isAnonymous()) {
			return null;
		}
		if ($declaringClass->getFileName() === null) {
			return null;
		}

		$stackName = sprintf('%s::%s', $declaringClass->getName(), $methodReflection->getName());
		if (array_key_exists($stackName, $this->calledMethodResults)) {
			return $this->calledMethodResults[$stackName];
		}

		if (array_key_exists($stackName, $this->calledMethodStack)) {
			return null;
		}

		if (count($this->calledMethodStack) > 0) {
			return null;
		}

		$this->calledMethodStack[$stackName] = true;

		$fileName = $this->fileHelper->normalizePath($declaringClass->getFileName());
		if (!isset($this->analysedFiles[$fileName])) {
			unset($this->calledMethodStack[$stackName]);
			return null;
		}
		$parserNodes = $this->parser->parseFile($fileName);

		$returnStatement = null;
		$this->processNodesForCalledMethod($parserNodes, new ExpressionResultStorage(), $fileName, $methodReflection, static function (Node $node, Scope $scope) use ($methodReflection, &$returnStatement): void {
			if (!$node instanceof MethodReturnStatementsNode) {
				return;
			}

			if ($node->getClassReflection()->getName() !== $methodReflection->getDeclaringClass()->getName()) {
				return;
			}

			if ($returnStatement !== null) {
				return;
			}

			$returnStatement = $node;
		});

		$calledMethodEndScope = null;
		if ($returnStatement !== null) {
			foreach ($returnStatement->getExecutionEnds() as $executionEnd) {
				$statementResult = $executionEnd->getStatementResult();
				$endNode = $executionEnd->getNode();
				if ($endNode instanceof Node\Stmt\Expression) {
					$exprType = $statementResult->getScope()->getType($endNode->expr);
					if ($exprType instanceof NeverType && $exprType->isExplicit()) {
						continue;
					}
				}
				if ($calledMethodEndScope === null) {
					$calledMethodEndScope = $statementResult->getScope();
					continue;
				}

				$calledMethodEndScope = $calledMethodEndScope->mergeWith($statementResult->getScope());
			}
			foreach ($returnStatement->getReturnStatements() as $statement) {
				if ($calledMethodEndScope === null) {
					$calledMethodEndScope = $statement->getScope();
					continue;
				}

				$calledMethodEndScope = $calledMethodEndScope->mergeWith($statement->getScope());
			}
		}

		unset($this->calledMethodStack[$stackName]);

		$this->calledMethodResults[$stackName] = $calledMethodEndScope;

		return $calledMethodEndScope;
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processNodesForCalledMethod($node, ExpressionResultStorage $storage, string $fileName, MethodReflection $methodReflection, callable $nodeCallback): void
	{
		if ($node instanceof Node) {
			$declaringClass = $methodReflection->getDeclaringClass();
			if (
				$node instanceof Node\Stmt\Class_
				&& isset($node->namespacedName)
				&& $declaringClass->getName() === (string) $node->namespacedName
				&& $declaringClass->getNativeReflection()->getStartLine() === $node->getStartLine()
			) {

				$stmts = $node->stmts;
				foreach ($stmts as $stmt) {
					if (!$stmt instanceof Node\Stmt\ClassMethod) {
						continue;
					}

					if ($stmt->name->toString() !== $methodReflection->getName()) {
						continue;
					}

					if ($stmt->getEndLine() - $stmt->getStartLine() > 50) {
						continue;
					}

					$scope = $this->scopeFactory->create(ScopeContext::create($fileName))->enterClass($declaringClass);
					$this->processStmtNode($stmt, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
				}
				return;
			}
			if ($node instanceof Node\Stmt\ClassLike) {
				return;
			}
			if ($node instanceof Node\FunctionLike) {
				return;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->{$subNodeName};
				$this->processNodesForCalledMethod($subNode, $storage, $fileName, $methodReflection, $nodeCallback);
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodesForCalledMethod($subNode, $storage, $fileName, $methodReflection, $nodeCallback);
			}
		}
	}

	/**
	 * @return array{TemplateTypeMap, array<string, Type>, array<string, bool>, array<string, Type>, ?Type, ?Type, ?string, bool, bool, bool, bool|null, bool, bool, string|null, Assertions, ?Type, array<string, Type>, array<(string|int), VarTag>, bool, ?ResolvedPhpDocBlock, array<string, bool>}
	 */
	public function getPhpDocs(Scope $scope, Node\FunctionLike|Node\Stmt\Property $node): array
	{
		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTypes = [];
		$phpDocImmediatelyInvokedCallableParameters = [];
		$phpDocClosureThisTypeParameters = [];
		$phpDocReturnType = null;
		$phpDocThrowType = null;
		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		$isPure = null;
		$isAllowedPrivateMutation = false;
		$acceptsNamedArguments = true;
		$isReadOnly = $scope->isInClass() && $scope->getClassReflection()->isImmutable();
		$asserts = Assertions::createEmpty();
		$selfOutType = null;
		$docComment = $node->getDocComment() !== null
			? $node->getDocComment()->getText()
			: null;

		$file = $scope->getFile();
		$class = $scope->isInClass() ? $scope->getClassReflection()->getName() : null;
		$trait = $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null;
		$resolvedPhpDoc = null;
		$functionName = null;
		$phpDocParameterOutTypes = [];
		$phpDocPureUnlessCallableIsImpureParameters = [];

		if ($node instanceof Node\Stmt\ClassMethod) {
			if (!$scope->isInClass()) {
				throw new ShouldNotHappenException();
			}
			$functionName = $node->name->name;
			$positionalParameterNames = array_map(static function (Node\Param $param): string {
				if (!$param->var instanceof Variable || !is_string($param->var->name)) {
					throw new ShouldNotHappenException();
				}

				return $param->var->name;
			}, $node->getParams());
			$currentResolvedPhpDoc = null;
			if ($docComment !== null) {
				$currentResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$file,
					$class,
					$trait,
					$node->name->name,
					$docComment,
				);
			}
			$methodNameForInheritance = $node->getAttribute('originalTraitMethodName') ?? $node->name->name;
			$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
				$scope->getClassReflection(),
				$methodNameForInheritance,
				$currentResolvedPhpDoc,
				$positionalParameterNames,
			);

			if ($node->name->toLowerString() === '__construct') {
				foreach ($node->params as $param) {
					if ($param->flags === 0) {
						continue;
					}

					if ($param->getDocComment() === null) {
						continue;
					}

					if (
						!$param->var instanceof Variable
						|| !is_string($param->var->name)
					) {
						throw new ShouldNotHappenException();
					}

					$paramPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
						$file,
						$class,
						$trait,
						'__construct',
						$param->getDocComment()->getText(),
					);
					$varTags = $paramPhpDoc->getVarTags();
					if (isset($varTags[0]) && count($varTags) === 1) {
						$phpDocType = $varTags[0]->getType();
					} elseif (isset($varTags[$param->var->name])) {
						$phpDocType = $varTags[$param->var->name]->getType();
					} else {
						continue;
					}

					$phpDocParameterTypes[$param->var->name] = $phpDocType;
				}
			}
		} elseif ($node instanceof Node\Stmt\Function_) {
			$functionName = trim($scope->getNamespace() . '\\' . $node->name->name, '\\');
		} elseif ($node instanceof Node\PropertyHook) {
			$propertyName = $node->getAttribute('propertyName');
			if ($propertyName !== null) {
				$functionName = sprintf('$%s::%s', $propertyName, $node->name->toString());
			}
		}

		if ($docComment !== null && $resolvedPhpDoc === null) {
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				$class,
				$trait,
				$functionName,
				$docComment,
			);
		}

		$varTags = [];
		if ($resolvedPhpDoc !== null) {
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
			$phpDocImmediatelyInvokedCallableParameters = $resolvedPhpDoc->getParamsImmediatelyInvokedCallable();
			foreach ($resolvedPhpDoc->getParamTags() as $paramName => $paramTag) {
				if (array_key_exists($paramName, $phpDocParameterTypes)) {
					continue;
				}
				$paramType = $paramTag->getType();
				if ($scope->isInClass()) {
					$paramType = $this->transformStaticType($scope->getClassReflection(), $paramType);
				}
				$phpDocParameterTypes[$paramName] = $paramType;
			}
			foreach ($resolvedPhpDoc->getParamClosureThisTags() as $paramName => $paramClosureThisTag) {
				if (array_key_exists($paramName, $phpDocClosureThisTypeParameters)) {
					continue;
				}
				$paramClosureThisType = $paramClosureThisTag->getType();
				if ($scope->isInClass()) {
					$paramClosureThisType = $this->transformStaticType($scope->getClassReflection(), $paramClosureThisType);
				}
				$phpDocClosureThisTypeParameters[$paramName] = $paramClosureThisType;
			}

			foreach ($resolvedPhpDoc->getParamOutTags() as $paramName => $paramOutTag) {
				$phpDocParameterOutTypes[$paramName] = $paramOutTag->getType();
			}
			if ($node instanceof Node\FunctionLike) {
				$nativeReturnType = $scope->getFunctionType($node->getReturnType(), false, false);
				$phpDocReturnType = $this->getPhpDocReturnType($resolvedPhpDoc, $nativeReturnType);
				if ($phpDocReturnType !== null && $scope->isInClass()) {
					$phpDocReturnType = $this->transformStaticType($scope->getClassReflection(), $phpDocReturnType);
				}
			}
			$phpDocThrowType = $resolvedPhpDoc->getThrowsTag() !== null ? $resolvedPhpDoc->getThrowsTag()->getType() : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$isFinal = $resolvedPhpDoc->isFinal();
			$isPure = $resolvedPhpDoc->isPure();
			$isAllowedPrivateMutation = $resolvedPhpDoc->isAllowedPrivateMutation();
			$acceptsNamedArguments = $resolvedPhpDoc->acceptsNamedArguments();
			$isReadOnly = $isReadOnly || $resolvedPhpDoc->isReadOnly();
			$asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
			$selfOutType = $resolvedPhpDoc->getSelfOutTag() !== null ? $resolvedPhpDoc->getSelfOutTag()->getType() : null;
			$varTags = $resolvedPhpDoc->getVarTags();
			$phpDocPureUnlessCallableIsImpureParameters = $resolvedPhpDoc->getParamsPureUnlessCallableIsImpure();
		}

		if ($acceptsNamedArguments && $scope->isInClass()) {
			$acceptsNamedArguments = $scope->getClassReflection()->acceptsNamedArguments();
		}

		if ($isPure === null && $node instanceof Node\FunctionLike && $scope->isInClass()) {
			$classResolvedPhpDoc = $scope->getClassReflection()->getResolvedPhpDoc();
			if ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsPure()) {
				if (
					strtolower($functionName ?? '') === '__construct'
					|| (
						($phpDocReturnType === null || !$phpDocReturnType->isVoid()->yes())
						&& !$scope->getFunctionType($node->getReturnType(), false, false)->isVoid()->yes()
					)
				) {
					$isPure = true;
				}
			} elseif ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsImpure()) {
				$isPure = false;
			}
		}

		return [$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $docComment, $asserts, $selfOutType, $phpDocParameterOutTypes, $varTags, $isAllowedPrivateMutation, $resolvedPhpDoc, $phpDocPureUnlessCallableIsImpureParameters];
	}

	private function transformStaticType(ClassReflection $declaringClass, Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($declaringClass): Type {
			if ($type instanceof StaticType) {
				$changedType = $type->changeBaseClass($declaringClass);
				if ($declaringClass->isFinal() && !$type instanceof ThisType) {
					$changedType = $changedType->getStaticObjectType();
				}
				return $traverse($changedType);
			}

			return $traverse($type);
		});
	}

	private function getPhpDocReturnType(ResolvedPhpDocBlock $resolvedPhpDoc, Type $nativeReturnType): ?Type
	{
		$returnTag = $resolvedPhpDoc->getReturnTag();

		if ($returnTag === null) {
			return null;
		}

		$phpDocReturnType = $returnTag->getType();

		if ($returnTag->isExplicit()) {
			return $phpDocReturnType;
		}

		if ($nativeReturnType->isSuperTypeOf(TemplateTypeHelper::resolveToBounds($phpDocReturnType))->yes()) {
			return $phpDocReturnType;
		}

		if ($phpDocReturnType instanceof UnionType) {
			$types = [];
			foreach ($phpDocReturnType->getTypes() as $innerType) {
				if (!$nativeReturnType->isSuperTypeOf($innerType)->yes()) {
					continue;
				}

				$types[] = $innerType;
			}

			if (count($types) === 0) {
				return null;
			}

			return TypeCombinator::union(...$types);
		}

		return null;
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

	private function inferForLoopExpressions(For_ $stmt, Expr $lastCondExpr, MutatingScope $bodyScope): MutatingScope
	{
		// infer $items[$i] type from for ($i = 0; $i < count($items); $i++) {...}

		if (
			// $i = 0
			count($stmt->init) === 1
			&& $stmt->init[0] instanceof Assign
			&& $stmt->init[0]->var instanceof Variable
			&& $stmt->init[0]->expr instanceof Node\Scalar\Int_
			&& $stmt->init[0]->expr->value === 0
			// $i++ or ++$i
			&& count($stmt->loop) === 1
			&& ($stmt->loop[0] instanceof Expr\PreInc || $stmt->loop[0] instanceof Expr\PostInc)
			&& $stmt->loop[0]->var instanceof Variable
		) {
			// $i < count($items)
			if (
				$lastCondExpr instanceof BinaryOp\Smaller
				&& $lastCondExpr->left instanceof Variable
				&& $lastCondExpr->right instanceof FuncCall
				&& $lastCondExpr->right->name instanceof Name
				&& !$lastCondExpr->right->isFirstClassCallable()
				&& in_array($lastCondExpr->right->name->toLowerString(), ['count', 'sizeof'], true)
				&& count($lastCondExpr->right->getArgs()) > 0
				&& $lastCondExpr->right->getArgs()[0]->value instanceof Variable
				&& is_string($stmt->init[0]->var->name)
				&& $stmt->init[0]->var->name === $stmt->loop[0]->var->name
				&& $stmt->init[0]->var->name === $lastCondExpr->left->name
			) {
				$arrayArg = $lastCondExpr->right->getArgs()[0]->value;
				$arrayType = $bodyScope->getType($arrayArg);
				if ($arrayType->isList()->yes()) {
					$bodyScope = $bodyScope->assignExpression(
						new ArrayDimFetch($lastCondExpr->right->getArgs()[0]->value, $lastCondExpr->left),
						$arrayType->getIterableValueType(),
						$bodyScope->getNativeType($arrayArg)->getIterableValueType(),
					);
				}
			}

			// count($items) > $i
			if (
				$lastCondExpr instanceof BinaryOp\Greater
				&& $lastCondExpr->right instanceof Variable
				&& $lastCondExpr->left instanceof FuncCall
				&& $lastCondExpr->left->name instanceof Name
				&& !$lastCondExpr->left->isFirstClassCallable()
				&& in_array($lastCondExpr->left->name->toLowerString(), ['count', 'sizeof'], true)
				&& count($lastCondExpr->left->getArgs()) > 0
				&& $lastCondExpr->left->getArgs()[0]->value instanceof Variable
				&& is_string($stmt->init[0]->var->name)
				&& $stmt->init[0]->var->name === $stmt->loop[0]->var->name
				&& $stmt->init[0]->var->name === $lastCondExpr->right->name
			) {
				$arrayArg = $lastCondExpr->left->getArgs()[0]->value;
				$arrayType = $bodyScope->getType($arrayArg);
				if ($arrayType->isList()->yes()) {
					$bodyScope = $bodyScope->assignExpression(
						new ArrayDimFetch($lastCondExpr->left->getArgs()[0]->value, $lastCondExpr->right),
						$arrayType->getIterableValueType(),
						$bodyScope->getNativeType($arrayArg)->getIterableValueType(),
					);
				}
			}
		}

		return $bodyScope;
	}

	private function getGlobalVariableType(string $variableName): Type
	{
		if ($variableName === 'argc') {
			return StaticTypeFactory::argc();
		}
		if ($variableName === 'argv') {
			return StaticTypeFactory::argv();
		}

		return new MixedType();
	}

}
