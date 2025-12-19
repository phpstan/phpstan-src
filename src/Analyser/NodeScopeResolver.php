<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use ArrayAccess;
use Closure;
use DivisionByZeroError;
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
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
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
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureThisExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Node\BooleanOrNode;
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
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
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
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\MatchExpressionArm;
use PHPStan\Node\MatchExpressionArmBody;
use PHPStan\Node\MatchExpressionArmCondition;
use PHPStan\Node\MatchExpressionNode;
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
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Parser\LineAttributesVisitor;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ReversePipeTransformerVisitor;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
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
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use ReflectionProperty;
use Throwable;
use Traversable;
use TypeError;
use UnhandledMatchError;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_key_last;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reverse;
use function array_slice;
use function array_values;
use function base64_decode;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;
use function usort;
use const PHP_VERSION_ID;
use const SORT_NUMERIC;

#[AutowiredService]
class NodeScopeResolver
{

	private const LOOP_SCOPE_ITERATIONS = 3;
	private const GENERALIZE_AFTER_ITERATION = 1;

	/** @var array<string, true> filePath(string) => bool(true) */
	private array $analysedFiles = [];

	/** @var array<string, true> */
	private array $earlyTerminatingMethodNames;

	/** @var array<string, true> */
	private array $calledMethodStack = [];

	/** @var array<string, MutatingScope|null> */
	private array $calledMethodResults = [];

	/**
	 * @param string[][] $earlyTerminatingMethodCalls className(string) => methods(string[])
	 * @param array<int, string> $earlyTerminatingFunctionCalls
	 */
	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		private readonly InitializerExprTypeResolver $initializerExprTypeResolver,
		#[AutowiredParameter(ref: '@nodeScopeResolverReflector')]
		private readonly Reflector $reflector,
		private readonly ClassReflectionFactory $classReflectionFactory,
		private readonly ParameterOutTypeExtensionProvider $parameterOutTypeExtensionProvider,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private readonly Parser $parser,
		private readonly FileTypeMapper $fileTypeMapper,
		private readonly PhpVersion $phpVersion,
		private readonly PhpDocInheritanceResolver $phpDocInheritanceResolver,
		private readonly FileHelper $fileHelper,
		private readonly TypeSpecifier $typeSpecifier,
		private readonly DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		private readonly ReadWritePropertiesExtensionProvider $readWritePropertiesExtensionProvider,
		private readonly ParameterClosureThisExtensionProvider $parameterClosureThisExtensionProvider,
		private readonly ParameterClosureTypeExtensionProvider $parameterClosureTypeExtensionProvider,
		private readonly ScopeFactory $scopeFactory,
		private readonly DeepNodeCloner $deepNodeCloner,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithLoopInitialAssignments,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithAlwaysIterableForeach,
		#[AutowiredParameter]
		private readonly bool $polluteScopeWithBlock,
		#[AutowiredParameter]
		private readonly array $earlyTerminatingMethodCalls,
		#[AutowiredParameter]
		private readonly array $earlyTerminatingFunctionCalls,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private readonly bool $implicitThrows,
		#[AutowiredParameter]
		private readonly bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter]
		private readonly bool $narrowMethodScopeFromConstructor,
	)
	{
		$earlyTerminatingMethodNames = [];
		foreach ($this->earlyTerminatingMethodCalls as $methodNames) {
			foreach ($methodNames as $methodName) {
				$earlyTerminatingMethodNames[strtolower($methodName)] = true;
			}
		}
		$this->earlyTerminatingMethodNames = $earlyTerminatingMethodNames;
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
		foreach ($nodes as $i => $node) {
			if (
				!$node instanceof Node\Stmt
				|| ($alreadyTerminated && !($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike))
			) {
				continue;
			}

			$statementResult = $this->processStmtNode($node, $scope, $expressionResultStorage, $nodeCallback, StatementContext::createTopLevel());
			$scope = $statementResult->getScope();
			if ($alreadyTerminated || !$statementResult->isAlwaysTerminating()) {
				continue;
			}

			$alreadyTerminated = true;
			$nextStmts = $this->getNextUnreachableStatements(array_slice($nodes, $i + 1), true);
			$this->processUnreachableStatement($nextStmts, $scope, $expressionResultStorage, $nodeCallback);
		}

		$this->processPendingFibers($expressionResultStorage);
	}

	private function storeResult(ExpressionResultStorage $storage, Expr $expr, ExpressionResult $result): void
	{
		$storage->storeResult($expr, $result->getBeforeScope());
		$this->processPendingFibersForRequestedExpr($storage, $expr, $result->getBeforeScope());
	}

	protected function processPendingFibers(ExpressionResultStorage $storage): void
	{
	}

	protected function processPendingFibersForRequestedExpr(ExpressionResultStorage $storage, Expr $expr, Scope $result): void
	{
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
		$result = $this->processStmtNodesInternal(
			$parentNode,
			$stmts,
			$scope,
			$storage,
			$nodeCallback,
			$context,
		)->toPublic();
		$this->processPendingFibers($storage);

		return $result;
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
			if ($alreadyTerminated && !($stmt instanceof Node\Stmt\Function_ || $stmt instanceof Node\Stmt\ClassLike)) {
				continue;
			}

			$isLast = $i === $stmtCount - 1;
			$statementResult = $this->processStmtNode(
				$stmt,
				$scope,
				$storage,
				$nodeCallback,
				$context,
			);
			$scope = $statementResult->getScope();
			$hasYield = $hasYield || $statementResult->hasYield();

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
	private function processStmtNode(
		Node\Stmt $stmt,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		if (
			!$stmt instanceof Static_
			&& !$stmt instanceof Foreach_
			&& !$stmt instanceof Node\Stmt\Global_
			&& !$stmt instanceof Node\Stmt\Property
			&& !$stmt instanceof Node\Stmt\ClassConst
			&& !$stmt instanceof Node\Stmt\Const_
		) {
			$scope = $this->processStmtVarAnnotation($scope, $storage, $stmt, null, $nodeCallback);
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
					return new InternalStatementResult($scope, false, false, [], [], []);
				}
				if ($methodReflection instanceof PhpMethodReflection) {
					$declaringTrait = $methodReflection->getDeclaringTrait();
					if ($declaringTrait === null || $declaringTrait->getName() !== $scope->getTraitReflection()->getName()) {
						return new InternalStatementResult($scope, false, false, [], [], []);
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

		$overridingThrowPoints = $this->getOverridingThrowPoints($stmt, $scope);

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
			[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, , $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts,, $phpDocParameterOutTypes] = $this->getPhpDocs($scope, $stmt);

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
			$statementResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $functionScope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $functionScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds, &$functionImpurePoints): void {
				$nodeCallback($node, $scope);
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
			}, StatementContext::createTopLevel())->toPublic();

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
		} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $storage, $nodeCallback);
			[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes] = $this->getPhpDocs($scope, $stmt);

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
				$statementResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $methodScope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $methodScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds, &$methodImpurePoints): void {
					$nodeCallback($node, $scope);
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
				}, StatementContext::createTopLevel())->toPublic();

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

				if ($isConstructor && $this->narrowMethodScopeFromConstructor) {
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
			$isAlwaysTerminating = false;
			foreach ($stmt->exprs as $echoExpr) {
				$result = $this->processExprNode($stmt, $echoExpr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			}

			$throwPoints = $overridingThrowPoints ?? $throwPoints;
			$impurePoints = [
				new ImpurePoint($scope, $stmt, 'echo', 'echo', true),
			];
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
		} elseif ($stmt instanceof Node\Stmt\Expression) {
			if ($stmt->expr instanceof Expr\Throw_) {
				$scope = $stmtScope;
			}
			$earlyTerminationExpr = $this->findEarlyTerminatingExpr($stmt->expr, $scope);
			$hasAssign = false;
			$currentScope = $scope;
			$result = $this->processExprNode($stmt, $stmt->expr, $scope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $currentScope, &$hasAssign): void {
				$nodeCallback($node, $scope);
				if ($scope->getAnonymousFunctionReflection() !== $currentScope->getAnonymousFunctionReflection()) {
					return;
				}
				if ($scope->getFunction() !== $currentScope->getFunction()) {
					return;
				}
				if (!$node instanceof VariableAssignNode && !$node instanceof PropertyAssignNode) {
					return;
				}

				$hasAssign = true;
			}, ExpressionContext::createTopLevel());
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
			}

			$scope = $this->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context)->getScope();
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
		} elseif ($stmt instanceof Node\Stmt\Trait_) {
			return new InternalStatementResult($scope, false, false, [], [], []);
		} elseif ($stmt instanceof Node\Stmt\ClassLike) {
			if (!$context->isTopLevel()) {
				return new InternalStatementResult($scope, false, false, [], [], []);
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
			if ($this->narrowMethodScopeFromConstructor) {
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
			}

			$this->processStmtNodesInternal($stmt, $classLikeStatements, $classScope, $storage, $classStatementsGatherer, $context);
			$this->callNodeCallback($nodeCallback, new ClassPropertiesNode($stmt, $this->readWritePropertiesExtensionProvider, $classStatementsGatherer->getProperties(), $classStatementsGatherer->getPropertyUsages(), $classStatementsGatherer->getMethodCalls(), $classStatementsGatherer->getReturnStatementsNodes(), $classStatementsGatherer->getPropertyAssigns(), $classReflection), $classScope, $storage);
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
			$conditionType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($stmt->cond) : $scope->getNativeType($stmt->cond))->toBoolean();
			$ifAlwaysTrue = $conditionType->isTrue()->yes();
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
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
				$elseIfConditionType = ($this->treatPhpDocTypesAsCertain ? $condScope->getType($elseif->cond) : $scope->getNativeType($elseif->cond))->toBoolean();
				$condResult = $this->processExprNode($stmt, $elseif->cond, $condScope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $condResult->getImpurePoints());
				$condScope = $condResult->getScope();
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
					$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope);
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

				$condScope = $condScope->filterByFalseyValue($elseif->cond);
				$scope = $condScope;
			}

			if ($stmt->else === null) {
				if (!$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
					$finalScope = $scope->mergeWith($finalScope);
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
					$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope);
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
				$this->callNodeCallback($nodeCallback, new VariableAssignNode($stmt->keyVar, new GetIterableKeyTypeExpr($stmt->expr)), $originalScope, $storage);
			}

			if ($stmt->valueVar instanceof Variable) {
				$this->callNodeCallback($nodeCallback, new VariableAssignNode($stmt->valueVar, new GetIterableValueTypeExpr($stmt->expr)), $originalScope, $storage);
			}

			$originalStorage = $storage;
			$storage = $originalStorage->duplicate();
			if ($context->isTopLevel()) {
				$originalScope = $this->polluteScopeWithAlwaysIterableForeach ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope;
				$bodyScope = $this->enterForeach($originalScope, $storage, $originalScope, $stmt, $nodeCallback);
				$count = 0;
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($this->polluteScopeWithAlwaysIterableForeach ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope);
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

			$bodyScope = $bodyScope->mergeWith($this->polluteScopeWithAlwaysIterableForeach ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope);
			$storage = $originalStorage;
			$bodyScope = $this->enterForeach($bodyScope, $storage, $originalScope, $stmt, $nodeCallback);
			$finalScopeResult = $this->processStmtNodesInternal($stmt, $stmt->stmts, $bodyScope, $storage, $nodeCallback, $context)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope();
			$scopesWithIterableValueType = [];

			$originalKeyVarExpr = null;
			$continueExitPointHasUnoriginalKeyType = false;
			if ($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)) {
				$originalKeyVarExpr = new OriginalForeachKeyExpr($stmt->keyVar->name);
				if ($finalScope->hasExpressionType($originalKeyVarExpr)->yes()) {
					$scopesWithIterableValueType[] = $finalScope;
				} else {
					$continueExitPointHasUnoriginalKeyType = true;
				}
			}

			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$continueScope = $continueExitPoint->getScope();
				$finalScope = $continueScope->mergeWith($finalScope);
				if ($originalKeyVarExpr === null || !$continueScope->hasExpressionType($originalKeyVarExpr)->yes()) {
					$continueExitPointHasUnoriginalKeyType = true;
					continue;
				}
				$scopesWithIterableValueType[] = $continueScope;
			}
			$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
			foreach ($breakExitPoints as $breakExitPoint) {
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
			}

			$exprType = $scope->getType($stmt->expr);
			$hasExpr = $scope->hasExpressionType($stmt->expr);
			if (
				count($breakExitPoints) === 0
				&& count($scopesWithIterableValueType) > 0
				&& !$continueExitPointHasUnoriginalKeyType
				&& $stmt->keyVar !== null
				&& $exprType->isArray()->yes()
				&& $exprType->isConstantArray()->no()
				&& (!$hasExpr->no() || !$stmt->expr instanceof Variable)
			) {
				$arrayExprDimFetch = new ArrayDimFetch($stmt->expr, $stmt->keyVar);
				$arrayDimFetchLoopTypes = [];
				foreach ($scopesWithIterableValueType as $scopeWithIterableValueType) {
					$arrayDimFetchLoopTypes[] = $scopeWithIterableValueType->getType($arrayExprDimFetch);
				}

				$arrayDimFetchLoopType = TypeCombinator::union(...$arrayDimFetchLoopTypes);

				$arrayDimFetchLoopNativeTypes = [];
				foreach ($scopesWithIterableValueType as $scopeWithIterableValueType) {
					$arrayDimFetchLoopNativeTypes[] = $scopeWithIterableValueType->getNativeType($arrayExprDimFetch);
				}

				$arrayDimFetchLoopNativeType = TypeCombinator::union(...$arrayDimFetchLoopNativeTypes);

				if (!$arrayDimFetchLoopType->equals($exprType->getIterableValueType())) {
					$newExprType = TypeTraverser::map($exprType, static function (Type $type, callable $traverse) use ($arrayDimFetchLoopType): Type {
						if ($type instanceof UnionType || $type instanceof IntersectionType) {
							return $traverse($type);
						}

						if (!$type instanceof ArrayType) {
							return $type;
						}

						return new ArrayType($type->getKeyType(), $arrayDimFetchLoopType);
					});
					$newExprNativeType = TypeTraverser::map($scope->getNativeType($stmt->expr), static function (Type $type, callable $traverse) use ($arrayDimFetchLoopNativeType): Type {
						if ($type instanceof UnionType || $type instanceof IntersectionType) {
							return $traverse($type);
						}

						if (!$type instanceof ArrayType) {
							return $type;
						}

						return new ArrayType($type->getKeyType(), $arrayDimFetchLoopNativeType);
					});

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
			if ($exprType->isIterable()->no() || $isIterableAtLeastOnce->maybe()) {
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
			if (!(new ObjectType(Traversable::class))->isSuperTypeOf($scope->getType($stmt->expr))->no()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $stmt->expr);
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
			$beforeCondBooleanType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($stmt->cond) : $scope->getNativeType($stmt->cond))->toBoolean();
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

			$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $bodyScopeMaybeRan->getType($stmt->cond) : $bodyScopeMaybeRan->getNativeType($stmt->cond))->toBoolean();
			$alwaysIterates = $condBooleanType->isTrue()->yes() && $context->isTopLevel();
			$neverIterates = $condBooleanType->isFalse()->yes() && $context->isTopLevel();
			if (!$alwaysIterates) {
				foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
					$finalScope = $finalScope->mergeWith($continueExitPoint->getScope());
				}
			}

			$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
			foreach ($breakExitPoints as $breakExitPoint) {
				$finalScope = $finalScope->mergeWith($breakExitPoint->getScope());
			}

			$isIterableAtLeastOnce = $beforeCondBooleanType->isTrue()->yes();
			$this->callNodeCallback($nodeCallback, new BreaklessWhileLoopNode($stmt, $finalScopeResult->toPublic()->getExitPoints()), $bodyScopeMaybeRan, $storage);

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
			$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $bodyScope->getType($stmt->cond) : $bodyScope->getNativeType($stmt->cond))->toBoolean();
			$alwaysIterates = $condBooleanType->isTrue()->yes() && $context->isTopLevel();

			$this->callNodeCallback($nodeCallback, new DoWhileLoopConditionNode($stmt->cond, $bodyScopeResult->toPublic()->getExitPoints()), $bodyScope, $storage);

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
			foreach ($bodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
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
			$storage = $originalStorage->duplicate();

			$bodyScope = $initScope;
			$isIterableAtLeastOnce = TrinaryLogic::createYes();
			$lastCondExpr = array_last($stmt->cond) ?? null;
			foreach ($stmt->cond as $condExpr) {
				$condResult = $this->processExprNode($stmt, $condExpr, $bodyScope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep());
				$initScope = $condResult->getScope();
				$condResultScope = $condResult->getScope();

				// only the last condition expression is relevant whether the loop continues
				// see https://www.php.net/manual/en/control-structures.for.php
				if ($condExpr === $lastCondExpr) {
					$condTruthiness = ($this->treatPhpDocTypesAsCertain ? $condResultScope->getType($condExpr) : $condResultScope->getNativeType($condExpr))->toBoolean();
					$isIterableAtLeastOnce = $isIterableAtLeastOnce->and($condTruthiness->isTrue());
				}

				$hasYield = $hasYield || $condResult->hasYield();
				$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $condResult->getImpurePoints());
				$bodyScope = $condResult->getTruthyScope();
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

			foreach ($finalScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
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
					$branchScope = $caseResult->getTruthyScope()->filterByTruthyValue($condExpr);
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
				$finalScope = $scope->mergeWith($finalScope);
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

				$originalCatchTypes = array_map(static fn (Name $name): Type => new ObjectType($name->toString()), $catchNode->types);
				$catchTypes = array_map(static fn (Type $type): Type => TypeCombinator::remove($type, $pastCatchTypes), $originalCatchTypes);

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

						foreach ($catchTypes as $catchTypeIndex => $catchTypeItem) {
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
				if (count($finallyResult->getExitPoints()) > 0) {
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

				$scope = $scope->assignVariable($var->name, new MixedType(), new MixedType(), TrinaryLogic::createYes());
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
				$scope = $scope->assignExpression(new ConstFetch($constantName), $scope->getType($const->value), $scope->getNativeType($const->value));
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
					$scope->getType($const->value),
					$scope->getNativeType($const->value),
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

		$enumAdapter = base64_decode('UEhQU3RhblxCZXR0ZXJSZWZsZWN0aW9uXFJlZmxlY3Rpb25cQWRhcHRlclxSZWZsZWN0aW9uRW51bQ==', true);

		return $this->classReflectionFactory->create(
			$betterReflectionClass->getName(),
			$betterReflectionClass instanceof ReflectionEnum && PHP_VERSION_ID >= 80000 ? new $enumAdapter($betterReflectionClass) : new ReflectionClass($betterReflectionClass),
			null,
			null,
			null,
			sprintf('%s:%d', $scope->getFile(), $stmt->getStartLine()),
		);
	}

	private function lookForSetAllowedUndefinedExpressions(MutatingScope $scope, Expr $expr): MutatingScope
	{
		return $this->lookForExpressionCallback($scope, $expr, static fn (MutatingScope $scope, Expr $expr): MutatingScope => $scope->setAllowedUndefinedExpression($expr));
	}

	private function lookForUnsetAllowedUndefinedExpressions(MutatingScope $scope, Expr $expr): MutatingScope
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
		} elseif ($expr instanceof PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
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

	private function ensureShallowNonNullability(MutatingScope $scope, Scope $originalScope, Expr $exprToSpecify): EnsuredNonNullabilityResult
	{
		$exprType = $scope->getType($exprToSpecify);
		$isNull = $exprType->isNull();
		if ($isNull->yes()) {
			return new EnsuredNonNullabilityResult($scope, []);
		}

		// keep certainty
		$certainty = TrinaryLogic::createYes();
		$hasExpressionType = $originalScope->hasExpressionType($exprToSpecify);
		if (!$hasExpressionType->no()) {
			$certainty = $hasExpressionType;
		}

		$exprTypeWithoutNull = TypeCombinator::removeNull($exprType);
		if ($exprType->equals($exprTypeWithoutNull)) {
			$originalExprType = $originalScope->getType($exprToSpecify);
			if (!$originalExprType->equals($exprTypeWithoutNull)) {
				$originalNativeType = $originalScope->getNativeType($exprToSpecify);

				return new EnsuredNonNullabilityResult($scope, [
					new EnsuredNonNullabilityResultExpression($exprToSpecify, $originalExprType, $originalNativeType, $certainty),
				]);
			}
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$nativeType = $scope->getNativeType($exprToSpecify);
		$scope = $scope->specifyExpressionType(
			$exprToSpecify,
			$exprTypeWithoutNull,
			TypeCombinator::removeNull($nativeType),
			TrinaryLogic::createYes(),
		);

		return new EnsuredNonNullabilityResult(
			$scope,
			[
				new EnsuredNonNullabilityResultExpression($exprToSpecify, $exprType, $nativeType, $certainty),
			],
		);
	}

	private function ensureNonNullability(MutatingScope $scope, Expr $expr): EnsuredNonNullabilityResult
	{
		$specifiedExpressions = [];
		$originalScope = $scope;
		$scope = $this->lookForExpressionCallback($scope, $expr, function ($scope, $expr) use (&$specifiedExpressions, $originalScope) {
			$result = $this->ensureShallowNonNullability($scope, $originalScope, $expr);
			foreach ($result->getSpecifiedExpressions() as $specifiedExpression) {
				$specifiedExpressions[] = $specifiedExpression;
			}
			return $result->getScope();
		});

		return new EnsuredNonNullabilityResult($scope, $specifiedExpressions);
	}

	/**
	 * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
	 */
	private function revertNonNullability(MutatingScope $scope, array $specifiedExpressions): MutatingScope
	{
		foreach ($specifiedExpressions as $specifiedExpressionResult) {
			$scope = $scope->specifyExpressionType(
				$specifiedExpressionResult->getExpression(),
				$specifiedExpressionResult->getOriginalType(),
				$specifiedExpressionResult->getOriginalNativeType(),
				$specifiedExpressionResult->getCertainty(),
			);
		}

		return $scope;
	}

	private function findEarlyTerminatingExpr(Expr $expr, Scope $scope): ?Expr
	{
		if (($expr instanceof MethodCall || $expr instanceof Expr\StaticCall) && $expr->name instanceof Node\Identifier) {
			if (array_key_exists($expr->name->toLowerString(), $this->earlyTerminatingMethodNames)) {
				if ($expr instanceof MethodCall) {
					$methodCalledOnType = $scope->getType($expr->var);
				} else {
					if ($expr->class instanceof Name) {
						$methodCalledOnType = $scope->resolveTypeByName($expr->class);
					} else {
						$methodCalledOnType = $scope->getType($expr->class);
					}
				}

				foreach ($methodCalledOnType->getObjectClassNames() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}

					$classReflection = $this->reflectionProvider->getClass($referencedClass);
					foreach (array_merge([$referencedClass], $classReflection->getParentClassesNames(), $classReflection->getNativeReflection()->getInterfaceNames()) as $className) {
						if (!isset($this->earlyTerminatingMethodCalls[$className])) {
							continue;
						}

						if (in_array((string) $expr->name, $this->earlyTerminatingMethodCalls[$className], true)) {
							return $expr;
						}
					}
				}
			}
		}

		if ($expr instanceof FuncCall && $expr->name instanceof Name) {
			if (in_array((string) $expr->name, $this->earlyTerminatingFunctionCalls, true)) {
				return $expr;
			}
		}

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
		/*$existingExprResult = $storage->findResult($expr);
		if ($existingExprResult !== null) {
			if ($nodeCallback instanceof ShallowNodeCallback) {
				return $existingExprResult;
			}
			throw new ShouldNotHappenException(sprintf('Expr %s on line %d has already been analysed', get_class($expr), $expr->getStartLine()));
		}*/

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
			$this->storeResult($storage, $expr, $newExprResult);
			return $newExprResult;
		}

		$originalScope = $scope;
		$this->callNodeCallbackWithExpression($nodeCallback, $expr, $scope, $storage, $context);

		if ($expr instanceof Variable) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$isAlwaysTerminating = $result->isAlwaysTerminating();
				$scope = $result->getScope();
			} elseif (in_array($expr->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
				$impurePoints[] = new ImpurePoint($scope, $expr, 'superglobal', 'access to superglobal variable', true);
			}
		} elseif ($expr instanceof Assign || $expr instanceof AssignRef) {
			$result = $this->processAssignVar(
				$scope,
				$storage,
				$stmt,
				$expr->var,
				$expr->expr,
				$nodeCallback,
				$context,
				function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context, $storage): ExpressionResult {
					$impurePoints = [];
					if ($expr instanceof AssignRef) {
						$referencedExpr = $expr->expr;
						while ($referencedExpr instanceof ArrayDimFetch) {
							$referencedExpr = $referencedExpr->var;
						}

						if ($referencedExpr instanceof PropertyFetch || $referencedExpr instanceof StaticPropertyFetch) {
							$impurePoints[] = new ImpurePoint(
								$scope,
								$expr,
								'propertyAssignByRef',
								'property assignment by reference',
								false,
							);
						}

						$scope = $scope->enterExpressionAssign($expr->expr);
					}

					if ($expr->var instanceof Variable && is_string($expr->var->name)) {
						$context = $context->enterRightSideAssign(
							$expr->var->name,
							$expr->expr,
						);
					}

					$beforeScope = $scope;
					$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $result->hasYield();
					$throwPoints = $result->getThrowPoints();
					$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
					$isAlwaysTerminating = $result->isAlwaysTerminating();
					$scope = $result->getScope();

					if ($expr instanceof AssignRef) {
						$scope = $scope->exitExpressionAssign($expr->expr);
					}

					$result = new ExpressionResult($scope, $beforeScope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
					$this->storeResult($storage, $expr, $result);

					return $result;
				},
				true,
			);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$vars = $this->getAssignedVariables($expr->var);
			if (count($vars) > 0) {
				$varChangedScope = false;
				$scope = $this->processVarAnnotation($scope, $vars, $stmt, $varChangedScope);
				if (!$varChangedScope) {
					$scope = $this->processStmtVarAnnotation($scope, $storage, $stmt, null, $nodeCallback);
				}
			}

			return new ExpressionResult(
				$scope,
				$originalScope,
				$hasYield,
				$isAlwaysTerminating,
				$throwPoints,
				$impurePoints,
				static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
		} elseif ($expr instanceof Expr\AssignOp) {
			$result = $this->processAssignVar(
				$scope,
				$storage,
				$stmt,
				$expr->var,
				$expr,
				$nodeCallback,
				$context,
				function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context, $storage): ExpressionResult {
					$originalScope = $scope;
					if ($expr instanceof Expr\AssignOp\Coalesce) {
						$scope = $scope->filterByFalseyValue(
							new BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
						);
					}

					$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
					if ($expr instanceof Expr\AssignOp\Coalesce) {
						$result = new ExpressionResult(
							$result->getScope()->mergeWith($originalScope),
							$originalScope,
							$result->hasYield(),
							$result->isAlwaysTerminating(),
							$result->getThrowPoints(),
							$result->getImpurePoints(),
						);
						$this->storeResult($storage, $expr, $result);

						return $result;
					}

					return $result;
				},
				$expr instanceof Expr\AssignOp\Coalesce,
			);
			$scope = $result->getScope();
			if (!$expr instanceof Expr\AssignOp\Coalesce) {
				$this->storeResult($storage, $expr, $result);
			}
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			if (
				($expr instanceof Expr\AssignOp\Div || $expr instanceof Expr\AssignOp\Mod) &&
				!$scope->getType($expr->expr)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
			) {
				$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
			}

			return new ExpressionResult(
				$scope,
				$originalScope,
				$hasYield,
				$isAlwaysTerminating,
				$throwPoints,
				$impurePoints,
				static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
		} elseif ($expr instanceof FuncCall) {
			$parametersAcceptor = null;
			$functionReflection = null;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			if ($expr->name instanceof Expr) {
				$nameType = $scope->getType($expr->name);
				if (!$nameType->isCallable()->no()) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$nameType->getCallableParametersAcceptors($scope),
						null,
					);
				}

				$nameResult = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $nameResult->getScope();
				$throwPoints = $nameResult->getThrowPoints();
				$impurePoints = $nameResult->getImpurePoints();
				$isAlwaysTerminating = $nameResult->isAlwaysTerminating();
				if ( // phpcs:ignore
					$nameType->isObject()->yes()
					&& $nameType->isCallable()->yes()
					&& (new ObjectType(Closure::class))->isSuperTypeOf($nameType)->no()
				) {
					// processed later
				} elseif ($parametersAcceptor instanceof CallableParametersAcceptor) {
					$callableThrowPoints = array_map(static fn (SimpleThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $expr, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $expr), $parametersAcceptor->getThrowPoints());
					if (!$this->implicitThrows) {
						$callableThrowPoints = array_values(array_filter($callableThrowPoints, static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit()));
					}
					$throwPoints = array_merge($throwPoints, $callableThrowPoints);
					$impurePoints = array_merge($impurePoints, array_map(static fn (SimpleImpurePoint $impurePoint) => new ImpurePoint($scope, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $parametersAcceptor->getImpurePoints()));

					$scope = $this->processImmediatelyCalledCallable($scope, $parametersAcceptor->getInvalidateExpressions(), $parametersAcceptor->getUsedVariables());
				}
			} elseif ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$expr->getArgs(),
					$functionReflection->getVariants(),
					$functionReflection->getNamedArgumentsVariants(),
				);
				$impurePoint = SimpleImpurePoint::createFromVariant($functionReflection, $parametersAcceptor, $scope, $expr->getArgs());
				if ($impurePoint !== null) {
					$impurePoints[] = new ImpurePoint($scope, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
				}
			} else {
				$impurePoints[] = new ImpurePoint(
					$scope,
					$expr,
					'functionCall',
					'call to unknown function',
					false,
				);
			}

			$normalizedExpr = $expr;
			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr) ?? $expr;
				$returnType = $parametersAcceptor->getReturnType();
				$isAlwaysTerminating = $isAlwaysTerminating || $returnType instanceof NeverType && $returnType->isExplicit();
			}

			if (
				$normalizedExpr->name instanceof Name
				&& $functionReflection !== null
				&& $functionReflection->getName() === 'clone'
				&& count($normalizedExpr->getArgs()) === 2
			) {
				$clonePropertiesArgType = $scope->getType($normalizedExpr->getArgs()[1]->value);
				$cloneExpr = new TypeExpr($scope->getType(new Expr\Clone_($normalizedExpr->getArgs()[0]->value)));
				$clonePropertiesArgTypeConstantArrays = $clonePropertiesArgType->getConstantArrays();
				foreach ($clonePropertiesArgTypeConstantArrays as $clonePropertiesArgTypeConstantArray) {
					foreach ($clonePropertiesArgTypeConstantArray->getKeyTypes() as $i => $clonePropertyKeyType) {
						$clonePropertyKeyTypeScalars = $clonePropertyKeyType->getConstantScalarValues();
						$propertyAttributes = $normalizedExpr->getAttributes();
						$propertyAttributes['inCloneWith'] = true;
						if (count($clonePropertyKeyTypeScalars) === 1) {
							$this->processVirtualAssign(
								$scope,
								$storage,
								$stmt,
								new PropertyFetch($cloneExpr, (string) $clonePropertyKeyTypeScalars[0], $propertyAttributes),
								new TypeExpr($clonePropertiesArgTypeConstantArray->getValueTypes()[$i]),
								$nodeCallback,
							);
							continue;
						}

						$this->processVirtualAssign(
							$scope,
							$storage,
							$stmt,
							new PropertyFetch($cloneExpr, new TypeExpr($clonePropertyKeyType), $propertyAttributes),
							new TypeExpr($clonePropertiesArgTypeConstantArray->getValueTypes()[$i]),
							$nodeCallback,
						);
					}
				}
			}

			$result = $this->processArgs($stmt, $functionReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();

			if ($normalizedExpr->name instanceof Expr) {
				$nameType = $scope->getType($normalizedExpr->name);
				if (
					$nameType->isObject()->yes()
					&& $nameType->isCallable()->yes()
					&& (new ObjectType(Closure::class))->isSuperTypeOf($nameType)->no()
				) {
					$invokeResult = $this->processExprNode(
						$stmt,
						new MethodCall($normalizedExpr->name, '__invoke', $normalizedExpr->getArgs(), $normalizedExpr->getAttributes()),
						$scope,
						$storage,
						new NoopNodeCallback(),
						$context->enterDeep(),
					);
					$throwPoints = array_merge($throwPoints, $invokeResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $invokeResult->getImpurePoints());
					$isAlwaysTerminating = $invokeResult->isAlwaysTerminating();
				}
			}

			if ($functionReflection !== null) {
				$functionThrowPoint = $this->getFunctionThrowPoint($functionReflection, $parametersAcceptor, $normalizedExpr, $scope);
				if ($functionThrowPoint !== null) {
					$throwPoints[] = $functionThrowPoint;
				}
			} else {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			}

			if (
				$parametersAcceptor instanceof ClosureType && count($parametersAcceptor->getImpurePoints()) > 0
				&& $scope->isInClass()
			) {
				$scope = $scope->invalidateExpression(new Variable('this'), true);
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['json_encode', 'json_decode'], true)
			) {
				$scope = $scope->invalidateExpression(new FuncCall(new Name('json_last_error'), []))
					->invalidateExpression(new FuncCall(new Name\FullyQualified('json_last_error'), []))
					->invalidateExpression(new FuncCall(new Name('json_last_error_msg'), []))
					->invalidateExpression(new FuncCall(new Name\FullyQualified('json_last_error_msg'), []));
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'file_put_contents'
				&& count($normalizedExpr->getArgs()) > 0
			) {
				$scope = $scope->invalidateExpression(new FuncCall(new Name('file_get_contents'), [$normalizedExpr->getArgs()[0]]))
					->invalidateExpression(new FuncCall(new Name\FullyQualified('file_get_contents'), [$normalizedExpr->getArgs()[0]]));
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['array_pop', 'array_shift'], true)
				&& count($normalizedExpr->getArgs()) >= 1
			) {
				$arrayArg = $normalizedExpr->getArgs()[0]->value;

				$arrayArgType = $scope->getType($arrayArg);
				$arrayArgNativeType = $scope->getNativeType($arrayArg);
				$isArrayPop = $functionReflection->getName() === 'array_pop';

				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$arrayArg,
					new NativeTypeExpr(
						$isArrayPop ? $arrayArgType->popArray() : $arrayArgType->shiftArray(),
						$isArrayPop ? $arrayArgNativeType->popArray() : $arrayArgNativeType->shiftArray(),
					),
					$nodeCallback,
				)->getScope();
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['array_push', 'array_unshift'], true)
				&& count($normalizedExpr->getArgs()) >= 2
			) {
				$arrayArg = $normalizedExpr->getArgs()[0]->value;

				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$arrayArg,
					new NativeTypeExpr(
						$this->getArrayFunctionAppendingType($functionReflection, $scope, $normalizedExpr),
						$this->getArrayFunctionAppendingType($functionReflection, $scope->doNotTreatPhpDocTypesAsCertain(), $normalizedExpr),
					),
					$nodeCallback,
				)->getScope();
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['fopen', 'file_get_contents'], true)
			) {
				$scope = $scope->assignVariable('http_response_header', TypeCombinator::intersect(new ArrayType(new IntegerType(), new StringType()), new AccessoryArrayListType()), new ArrayType(new IntegerType(), new StringType()), TrinaryLogic::createYes());
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'shuffle'
			) {
				$arrayArg = $normalizedExpr->getArgs()[0]->value;

				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$arrayArg,
					new NativeTypeExpr($scope->getType($arrayArg)->shuffleArray(), $scope->getNativeType($arrayArg)->shuffleArray()),
					$nodeCallback,
				)->getScope();
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'array_splice'
				&& count($normalizedExpr->getArgs()) >= 2
			) {
				$arrayArg = $normalizedExpr->getArgs()[0]->value;
				$arrayArgType = $scope->getType($arrayArg);
				$arrayArgNativeType = $scope->getNativeType($arrayArg);

				$offsetType = $scope->getType($normalizedExpr->getArgs()[1]->value);
				$lengthType = isset($normalizedExpr->getArgs()[2]) ? $scope->getType($normalizedExpr->getArgs()[2]->value) : new NullType();
				$replacementType = isset($normalizedExpr->getArgs()[3]) ? $scope->getType($normalizedExpr->getArgs()[3]->value) : new ConstantArrayType([], []);

				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$arrayArg,
					new NativeTypeExpr(
						$arrayArgType->spliceArray($offsetType, $lengthType, $replacementType),
						$arrayArgNativeType->spliceArray($offsetType, $lengthType, $replacementType),
					),
					$nodeCallback,
				)->getScope();
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['sort', 'rsort', 'usort'], true)
				&& count($normalizedExpr->getArgs()) >= 1
			) {
				$arrayArg = $normalizedExpr->getArgs()[0]->value;

				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$arrayArg,
					new NativeTypeExpr($this->getArraySortPreserveListFunctionType($scope->getType($arrayArg)), $this->getArraySortPreserveListFunctionType($scope->getNativeType($arrayArg))),
					$nodeCallback,
				)->getScope();
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['natcasesort', 'natsort', 'arsort', 'asort', 'ksort', 'krsort', 'uasort', 'uksort'], true)
				&& count($normalizedExpr->getArgs()) >= 1
			) {
				$arrayArg = $normalizedExpr->getArgs()[0]->value;

				$scope = $this->processVirtualAssign(
					$scope,
					$storage,
					$stmt,
					$arrayArg,
					new NativeTypeExpr($this->getArraySortDoNotPreserveListFunctionType($scope->getType($arrayArg)), $this->getArraySortDoNotPreserveListFunctionType($scope->getNativeType($arrayArg))),
					$nodeCallback,
				)->getScope();
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'extract'
			) {
				$extractedArg = $normalizedExpr->getArgs()[0]->value;
				$extractedType = $scope->getType($extractedArg);
				$constantArrays = $extractedType->getConstantArrays();
				if (count($constantArrays) > 0) {
					$properties = [];
					$optionalProperties = [];
					$refCount = [];
					foreach ($constantArrays as $constantArray) {
						foreach ($constantArray->getKeyTypes() as $i => $keyType) {
							if ($keyType->isString()->no()) {
								// integers as variable names not allowed
								continue;
							}
							$key = (string) $keyType->getValue();
							$valueType = $constantArray->getValueTypes()[$i];
							$optional = $constantArray->isOptionalKey($i);
							if ($optional) {
								$optionalProperties[] = $key;
							}
							if (isset($properties[$key])) {
								$properties[$key] = TypeCombinator::union($properties[$key], $valueType);
								$refCount[$key]++;
							} else {
								$properties[$key] = $valueType;
								$refCount[$key] = 1;
							}
						}
					}
					foreach ($properties as $name => $type) {
						$optional = in_array($name, $optionalProperties, true) || $refCount[$name] < count($constantArrays);
						$scope = $scope->assignVariable($name, $type, $type, $optional ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes());
					}
				} else {
					$scope = $scope->afterExtractCall();
				}
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['clearstatcache', 'unlink'], true)
			) {
				$scope = $scope->afterClearstatcacheCall();
			}

			if (
				$functionReflection !== null
				&& str_starts_with($functionReflection->getName(), 'openssl')
			) {
				$scope = $scope->afterOpenSslCall($functionReflection->getName());
			}

		} elseif ($expr instanceof MethodCall) {
			$originalScope = $scope;
			if (
				($expr->var instanceof Expr\Closure || $expr->var instanceof Expr\ArrowFunction)
				&& $expr->name instanceof Node\Identifier
				&& strtolower($expr->name->name) === 'call'
				&& isset($expr->getArgs()[0])
			) {
				$closureCallScope = $scope->enterClosureCall(
					$scope->getType($expr->getArgs()[0]->value),
					$scope->getNativeType($expr->getArgs()[0]->value),
				);
			}

			$result = $this->processExprNode($stmt, $expr->var, $closureCallScope ?? $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
			if (isset($closureCallScope)) {
				$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
			}
			$parametersAcceptor = null;
			$methodReflection = null;
			$calledOnType = $scope->getType($expr->var);
			if ($expr->name instanceof Expr) {
				$methodNameResult = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$throwPoints = array_merge($throwPoints, $methodNameResult->getThrowPoints());
				$scope = $methodNameResult->getScope();
			} else {
				$methodName = $expr->name->name;
				$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
				if ($methodReflection !== null) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$methodReflection->getVariants(),
						$methodReflection->getNamedArgumentsVariants(),
					);

					$methodThrowPoint = $this->getMethodThrowPoint($methodReflection, $parametersAcceptor, $expr, $scope);
					if ($methodThrowPoint !== null) {
						$throwPoints[] = $methodThrowPoint;
					}
				}
			}

			if ($methodReflection !== null) {
				$impurePoint = SimpleImpurePoint::createFromVariant($methodReflection, $parametersAcceptor, $scope, $expr->getArgs());
				if ($impurePoint !== null) {
					$impurePoints[] = new ImpurePoint($scope, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
				}
			} else {
				$impurePoints[] = new ImpurePoint(
					$scope,
					$expr,
					'methodCall',
					'call to unknown method',
					false,
				);
			}

			$normalizedExpr = $expr;
			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $expr) ?? $expr;
				$returnType = $parametersAcceptor->getReturnType();
				$isAlwaysTerminating = $returnType instanceof NeverType && $returnType->isExplicit();
			}

			$result = $this->processArgs(
				$stmt,
				$methodReflection,
				$methodReflection !== null ? $scope->getNakedMethod($calledOnType, $methodReflection->getName()) : null,
				$parametersAcceptor,
				$normalizedExpr,
				$scope,
				$storage,
				$nodeCallback,
				$context,
			);
			$scope = $result->getScope();

			if ($methodReflection !== null) {
				$hasSideEffects = $methodReflection->hasSideEffects();
				if ($hasSideEffects->yes() || $methodReflection->getName() === '__construct') {
					$this->callNodeCallback($nodeCallback, new InvalidateExprNode($normalizedExpr->var), $scope, $storage);
					$scope = $scope->invalidateExpression($normalizedExpr->var, true);
				}
				if ($parametersAcceptor !== null && !$methodReflection->isStatic()) {
					$selfOutType = $methodReflection->getSelfOutType();
					if ($selfOutType !== null) {
						$scope = $scope->assignExpression(
							$normalizedExpr->var,
							TemplateTypeHelper::resolveTemplateTypes(
								$selfOutType,
								$parametersAcceptor->getResolvedTemplateTypeMap(),
								$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
								TemplateTypeVariance::createCovariant(),
							),
							$scope->getNativeType($normalizedExpr->var),
						);
					}
				}

				if (
					$scope->isInClass()
					&& $scope->getClassReflection()->getName() === $methodReflection->getDeclaringClass()->getName()
					/*&& (
						// should not be allowed but in practice has to be
						$scope->getClassReflection()->isFinal()
						|| $methodReflection->isFinal()->yes()
						|| $methodReflection->isPrivate()
					)*/
					&& TypeUtils::findThisType($calledOnType) !== null
				) {
					$calledMethodScope = $this->processCalledMethod($methodReflection);
					if ($calledMethodScope !== null) {
						$scope = $scope->mergeInitializedProperties($calledMethodScope);
					}
				}
			} else {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			}
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
		} elseif ($expr instanceof Expr\NullsafeMethodCall) {
			$beforeScope = $scope;
			$nonNullabilityResult = $this->ensureShallowNonNullability($scope, $scope, $expr->var);
			$exprResult = $this->processExprNode(
				$stmt,
				new MethodCall(
					$expr->var,
					$expr->name,
					$expr->args,
					array_merge($expr->getAttributes(), ['virtualNullsafeMethodCall' => true]),
				),
				$nonNullabilityResult->getScope(),
				$storage,
				$nodeCallback,
				$context,
			);
			$scope = $this->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

			$result = new ExpressionResult(
				$scope,
				$beforeScope,
				$exprResult->hasYield(),
				false,
				$exprResult->getThrowPoints(),
				$exprResult->getImpurePoints(),
				static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
			$this->storeResult($storage, $expr, $result);

			return $result;
		} elseif ($expr instanceof StaticCall) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			if ($expr->class instanceof Expr) {
				$classResult = $this->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $classResult->hasYield();
				$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $classResult->getImpurePoints());
				$isAlwaysTerminating = $classResult->isAlwaysTerminating();

				$scope = $classResult->getScope();
			}

			$parametersAcceptor = null;
			$methodReflection = null;
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$scope = $result->getScope();
			} elseif ($expr->class instanceof Name) {
				$classType = $scope->resolveTypeByName($expr->class);
				$methodName = $expr->name->name;
				if ($classType->hasMethod($methodName)->yes()) {
					$methodReflection = $classType->getMethod($methodName, $scope);
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$methodReflection->getVariants(),
						$methodReflection->getNamedArgumentsVariants(),
					);

					$methodThrowPoint = $this->getStaticMethodThrowPoint($methodReflection, $parametersAcceptor, $expr, $scope);
					if ($methodThrowPoint !== null) {
						$throwPoints[] = $methodThrowPoint;
					}

					$declaringClass = $methodReflection->getDeclaringClass();
					if (
						$declaringClass->getName() === 'Closure'
						&& strtolower($methodName) === 'bind'
					) {
						$thisType = null;
						$nativeThisType = null;
						if (isset($expr->getArgs()[1])) {
							$argType = $scope->getType($expr->getArgs()[1]->value);
							if ($argType->isNull()->yes()) {
								$thisType = null;
							} else {
								$thisType = $argType;
							}

							$nativeArgType = $scope->getNativeType($expr->getArgs()[1]->value);
							if ($nativeArgType->isNull()->yes()) {
								$nativeThisType = null;
							} else {
								$nativeThisType = $nativeArgType;
							}
						}
						$scopeClasses = ['static'];
						if (isset($expr->getArgs()[2])) {
							$argValue = $expr->getArgs()[2]->value;
							$argValueType = $scope->getType($argValue);

							$directClassNames = $argValueType->getObjectClassNames();
							if (count($directClassNames) > 0) {
								$scopeClasses = $directClassNames;
								$thisTypes = [];
								foreach ($directClassNames as $directClassName) {
									$thisTypes[] = new ObjectType($directClassName);
								}
								$thisType = TypeCombinator::union(...$thisTypes);
							} else {
								$thisType = $argValueType->getClassStringObjectType();
								$scopeClasses = $thisType->getObjectClassNames();
							}
						}
						$closureBindScope = $scope->enterClosureBind($thisType, $nativeThisType, $scopeClasses);
					}
				} else {
					$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
				}
			}

			if ($expr->class instanceof Expr) {
				$objectClasses = $scope->getType($expr->class)->getObjectClassNames();
				if (count($objectClasses) !== 1) {
					$objectClasses = $scope->getType(new New_($expr->class))->getObjectClassNames();
				}
				if (count($objectClasses) === 1) {
					$objectExprResult = $this->processExprNode($stmt, new StaticCall(new Name($objectClasses[0]), $expr->name, []), $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
					$additionalThrowPoints = $objectExprResult->getThrowPoints();
				} else {
					$additionalThrowPoints = [InternalThrowPoint::createImplicit($scope, $expr)];
				}
				foreach ($additionalThrowPoints as $throwPoint) {
					$throwPoints[] = $throwPoint;
				}
			}

			if ($methodReflection !== null) {
				$impurePoint = SimpleImpurePoint::createFromVariant($methodReflection, $parametersAcceptor, $scope, $expr->getArgs());
				if ($impurePoint !== null) {
					$impurePoints[] = new ImpurePoint($scope, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
				}
			} else {
				$impurePoints[] = new ImpurePoint(
					$scope,
					$expr,
					'methodCall',
					'call to unknown method',
					false,
				);
			}

			$normalizedExpr = $expr;
			if ($parametersAcceptor !== null) {
				$normalizedExpr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
				$returnType = $parametersAcceptor->getReturnType();
				$isAlwaysTerminating = $returnType instanceof NeverType && $returnType->isExplicit();
			}
			$result = $this->processArgs($stmt, $methodReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context, $closureBindScope ?? null);
			$scope = $result->getScope();
			$scopeFunction = $scope->getFunction();

			if (
				$methodReflection !== null
				&& (
					$methodReflection->hasSideEffects()->yes()
					|| (
						!$methodReflection->isStatic()
						&& $methodReflection->getName() === '__construct'
					)
				)
				&& $scope->isInClass()
				&& $scope->getClassReflection()->is($methodReflection->getDeclaringClass()->getName())
			) {
				$scope = $scope->invalidateExpression(new Variable('this'), true);
			}

			if (
				$methodReflection !== null
				&& !$methodReflection->isStatic()
				&& $methodReflection->getName() === '__construct'
				&& $scopeFunction instanceof MethodReflection
				&& !$scopeFunction->isStatic()
				&& $scope->isInClass()
				&& $scope->getClassReflection()->isSubclassOfClass($methodReflection->getDeclaringClass())
			) {
				$thisType = $scope->getType(new Variable('this'));
				$methodClassReflection = $methodReflection->getDeclaringClass();
				foreach ($methodClassReflection->getNativeReflection()->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
					if (!$property->isPromoted() || $property->getDeclaringClass()->getName() !== $methodClassReflection->getName()) {
						continue;
					}

					$scope = $scope->assignInitializedProperty($thisType, $property->getName());
				}
			}

			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
		} elseif ($expr instanceof PropertyFetch) {
			$scopeBeforeVar = $scope;
			$result = $this->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
				$scope = $result->getScope();
				if ($this->phpVersion->supportsPropertyHooks()) {
					$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
				}
			} else {
				$propertyName = $expr->name->toString();
				$propertyHolderType = $scopeBeforeVar->getType($expr->var);
				$propertyReflection = $scopeBeforeVar->getInstancePropertyReflection($propertyHolderType, $propertyName);
				if ($propertyReflection !== null && $this->phpVersion->supportsPropertyHooks()) {
					$propertyDeclaringClass = $propertyReflection->getDeclaringClass();
					if ($propertyDeclaringClass->hasNativeProperty($propertyName)) {
						$nativeProperty = $propertyDeclaringClass->getNativeProperty($propertyName);
						$throwPoints = array_merge($throwPoints, $this->getPropertyReadThrowPointsFromGetHook($scopeBeforeVar, $expr, $nativeProperty));
					}
				}
			}
		} elseif ($expr instanceof Expr\NullsafePropertyFetch) {
			$beforeScope = $scope;
			$nonNullabilityResult = $this->ensureShallowNonNullability($scope, $scope, $expr->var);
			$exprResult = $this->processExprNode($stmt, new PropertyFetch(
				$expr->var,
				$expr->name,
				array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch' => true]),
			), $nonNullabilityResult->getScope(), $storage, $nodeCallback, $context);
			$scope = $this->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

			$result = new ExpressionResult(
				$scope,
				$beforeScope,
				$exprResult->hasYield(),
				false,
				$exprResult->getThrowPoints(),
				$exprResult->getImpurePoints(),
				static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
			$this->storeResult($storage, $expr, $result);

			return $result;
		} elseif ($expr instanceof StaticPropertyFetch) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [
				new ImpurePoint(
					$scope,
					$expr,
					'staticPropertyAccess',
					'static property access',
					true,
				),
			];
			$isAlwaysTerminating = false;
			if ($expr->class instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$isAlwaysTerminating = $result->isAlwaysTerminating();
				$scope = $result->getScope();
			}
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof Expr\Closure) {
			$beforeScope = $scope;
			$processClosureResult = $this->processClosureNode($stmt, $expr, $scope, $storage, $nodeCallback, $context, null);
			$scope = $processClosureResult->getScope();

			$result = new ExpressionResult(
				$scope,
				$beforeScope,
				false,
				false,
				[],
				[],
			);
			$this->storeResult($storage, $expr, $result);

			return $result;
		} elseif ($expr instanceof Expr\ArrowFunction) {
			$result = $this->processArrowFunctionNode($stmt, $expr, $scope, $storage, $nodeCallback, null);
			$exprResult = new ExpressionResult(
				$result->getScope(),
				$scope,
				$result->hasYield(),
				false,
				[],
				[],
			);
			$this->storeResult($storage, $expr, $exprResult);
			return $exprResult;
		} elseif ($expr instanceof ErrorSuppress) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
		} elseif ($expr instanceof Exit_) {
			$hasYield = false;
			$throwPoints = [];
			$kind = $expr->getAttribute('kind', Exit_::KIND_EXIT);
			$identifier = $kind === Exit_::KIND_DIE ? 'die' : 'exit';
			$impurePoints = [
				new ImpurePoint($scope, $expr, $identifier, $identifier, true),
			];
			$isAlwaysTerminating = true;
			if ($expr->expr !== null) {
				$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof Node\Scalar\InterpolatedString) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			foreach ($expr->parts as $part) {
				if (!$part instanceof Expr) {
					continue;
				}
				$result = $this->processExprNode($stmt, $part, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof ArrayDimFetch) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			if ($expr->dim !== null) {
				$result = $this->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$isAlwaysTerminating = $result->isAlwaysTerminating();
				$scope = $result->getScope();
			}

			$result = $this->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();
		} elseif ($expr instanceof Array_) {
			$itemNodes = [];
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			foreach ($expr->items as $arrayItem) {
				$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
				$this->callNodeCallback($nodeCallback, $arrayItem, $scope, $storage);
				if ($arrayItem->key !== null) {
					$keyResult = $this->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $keyResult->hasYield();
					$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
					$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
					$scope = $keyResult->getScope();
				}

				$valueResult = $this->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $valueResult->hasYield();
				$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
				$scope = $valueResult->getScope();
			}
			$this->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);
		} elseif ($expr instanceof BooleanAnd || $expr instanceof BinaryOp\LogicalAnd) {
			$leftResult = $this->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
			$rightResult = $this->processExprNode($stmt, $expr->right, $leftResult->getTruthyScope(), $storage, $nodeCallback, $context);
			$rightExprType = $rightResult->getScope()->getType($expr->right);
			if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
				$leftMergedWithRightScope = $leftResult->getFalseyScope();
			} else {
				$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
			}

			$this->callNodeCallbackWithExpression($nodeCallback, new BooleanAndNode($expr, $leftResult->getTruthyScope()), $scope, $storage, $context);

			$result = new ExpressionResult(
				$leftMergedWithRightScope,
				$scope,
				$leftResult->hasYield() || $rightResult->hasYield(),
				$leftResult->isAlwaysTerminating(),
				array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
				array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
				static fn (): MutatingScope => $rightResult->getScope()->filterByTruthyValue($expr),
				static fn (): MutatingScope => $leftMergedWithRightScope->filterByFalseyValue($expr),
			);
			$this->storeResult($storage, $expr, $result);
			return $result;
		} elseif ($expr instanceof BooleanOr || $expr instanceof BinaryOp\LogicalOr) {
			$leftResult = $this->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
			$rightResult = $this->processExprNode($stmt, $expr->right, $leftResult->getFalseyScope(), $storage, $nodeCallback, $context);
			$rightExprType = $rightResult->getScope()->getType($expr->right);
			if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
				$leftMergedWithRightScope = $leftResult->getTruthyScope();
			} else {
				$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
			}

			$this->callNodeCallbackWithExpression($nodeCallback, new BooleanOrNode($expr, $leftResult->getFalseyScope()), $scope, $storage, $context);

			$result = new ExpressionResult(
				$leftMergedWithRightScope,
				$scope,
				$leftResult->hasYield() || $rightResult->hasYield(),
				$leftResult->isAlwaysTerminating(),
				array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
				array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
				static fn (): MutatingScope => $leftMergedWithRightScope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $rightResult->getScope()->filterByFalseyValue($expr),
			);
			$this->storeResult($storage, $expr, $result);
			return $result;
		} elseif ($expr instanceof Coalesce) {
			$nonNullabilityResult = $this->ensureNonNullability($scope, $expr->left);
			$condScope = $this->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->left);
			$condResult = $this->processExprNode($stmt, $expr->left, $condScope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $this->revertNonNullability($condResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());
			$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $expr->left);

			$rightScope = $scope->filterByFalseyValue($expr);
			$rightResult = $this->processExprNode($stmt, $expr->right, $rightScope, $storage, $nodeCallback, $context->enterDeep());
			$rightExprType = $scope->getType($expr->right);
			if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
				$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]));
			} else {
				$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]))->mergeWith($rightResult->getScope());
			}

			$hasYield = $condResult->hasYield() || $rightResult->hasYield();
			$throwPoints = array_merge($condResult->getThrowPoints(), $rightResult->getThrowPoints());
			$impurePoints = array_merge($condResult->getImpurePoints(), $rightResult->getImpurePoints());
			$isAlwaysTerminating = $condResult->isAlwaysTerminating();
		} elseif ($expr instanceof BinaryOp\Pipe) {
			if ($expr->right instanceof FuncCall && $expr->right->isFirstClassCallable()) {
				$exprResult = $this->processExprNode($stmt, new FuncCall($expr->right->name, [
					new Arg($expr->left, attributes: $expr->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, [])),
				], array_merge($expr->right->getAttributes(), ['virtualPipeOperatorCall' => true])), $scope, $storage, $nodeCallback, $context);
				$scope = $exprResult->getScope();
				$hasYield = $exprResult->hasYield();
				$throwPoints = $exprResult->getThrowPoints();
				$impurePoints = $exprResult->getImpurePoints();
				$isAlwaysTerminating = $exprResult->isAlwaysTerminating();
			} elseif ($expr->right instanceof MethodCall && $expr->right->isFirstClassCallable()) {
				$exprResult = $this->processExprNode($stmt, new MethodCall($expr->right->var, $expr->right->name, [
					new Arg($expr->left, attributes: $expr->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, [])),
				], array_merge($expr->right->getAttributes(), ['virtualPipeOperatorCall' => true])), $scope, $storage, $nodeCallback, $context);
				$scope = $exprResult->getScope();
				$hasYield = $exprResult->hasYield();
				$throwPoints = $exprResult->getThrowPoints();
				$impurePoints = $exprResult->getImpurePoints();
				$isAlwaysTerminating = $exprResult->isAlwaysTerminating();
			} elseif ($expr->right instanceof StaticCall && $expr->right->isFirstClassCallable()) {
				$exprResult = $this->processExprNode($stmt, new StaticCall($expr->right->class, $expr->right->name, [
					new Arg($expr->left, attributes: $expr->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, [])),
				], array_merge($expr->right->getAttributes(), ['virtualPipeOperatorCall' => true])), $scope, $storage, $nodeCallback, $context);
				$scope = $exprResult->getScope();
				$hasYield = $exprResult->hasYield();
				$throwPoints = $exprResult->getThrowPoints();
				$impurePoints = $exprResult->getImpurePoints();
				$isAlwaysTerminating = $exprResult->isAlwaysTerminating();
			} else {
				$exprResult = $this->processExprNode($stmt, new FuncCall($expr->right, [
					new Arg($expr->left, attributes: $expr->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, [])),
				], array_merge($expr->right->getAttributes(), ['virtualPipeOperatorCall' => true])), $scope, $storage, $nodeCallback, $context);
				$scope = $exprResult->getScope();
				$hasYield = $exprResult->hasYield();
				$throwPoints = $exprResult->getThrowPoints();
				$impurePoints = $exprResult->getImpurePoints();
				$isAlwaysTerminating = $exprResult->isAlwaysTerminating();
			}
		} elseif ($expr instanceof BinaryOp) {
			$result = $this->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$result = $this->processExprNode($stmt, $expr->right, $scope, $storage, $nodeCallback, $context->enterDeep());
			if (
				($expr instanceof BinaryOp\Div || $expr instanceof BinaryOp\Mod) &&
				!$scope->getType($expr->right)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
			) {
				$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
			}
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
		} elseif ($expr instanceof Expr\Include_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			$impurePoints = $result->getImpurePoints();
			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				in_array($expr->type, [Expr\Include_::TYPE_INCLUDE, Expr\Include_::TYPE_INCLUDE_ONCE], true) ? 'include' : 'require',
				in_array($expr->type, [Expr\Include_::TYPE_INCLUDE, Expr\Include_::TYPE_INCLUDE_ONCE], true) ? 'include' : 'require',
				true,
			);
			$hasYield = $result->hasYield();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope()->afterExtractCall();
		} elseif ($expr instanceof Expr\Print_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$impurePoints[] = new ImpurePoint($scope, $expr, 'print', 'print', true);
			$hasYield = $result->hasYield();

			$scope = $result->getScope();
		} elseif ($expr instanceof Cast\String_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$hasYield = $result->hasYield();

			$exprType = $scope->getType($expr->expr);
			$toStringMethod = $scope->getMethodReflection($exprType, '__toString');
			if ($toStringMethod !== null) {
				if (!$toStringMethod->hasSideEffects()->no()) {
					$impurePoints[] = new ImpurePoint(
						$scope,
						$expr,
						'methodCall',
						sprintf('call to method %s::%s()', $toStringMethod->getDeclaringClass()->getDisplayName(), $toStringMethod->getName()),
						$toStringMethod->isPure()->no(),
					);
				}
			}

			$scope = $result->getScope();
		} elseif (
			$expr instanceof Expr\BitwiseNot
			|| $expr instanceof Cast
			|| $expr instanceof Expr\Clone_
			|| $expr instanceof Expr\UnaryMinus
			|| $expr instanceof Expr\UnaryPlus
		) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$hasYield = $result->hasYield();

			$scope = $result->getScope();
		} elseif ($expr instanceof Expr\Eval_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			$impurePoints = $result->getImpurePoints();
			$impurePoints[] = new ImpurePoint($scope, $expr, 'eval', 'eval', true);
			$hasYield = $result->hasYield();
			$isAlwaysTerminating = $result->isAlwaysTerminating();

			$scope = $result->getScope();
		} elseif ($expr instanceof Expr\YieldFrom) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
			$impurePoints = $result->getImpurePoints();
			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'yieldFrom',
				'yield from',
				true,
			);
			$hasYield = true;
			$isAlwaysTerminating = $result->isAlwaysTerminating();

			$scope = $result->getScope();
		} elseif ($expr instanceof BooleanNot) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
		} elseif ($expr instanceof Expr\ClassConstFetch) {
			$isAlwaysTerminating = false;

			if ($expr->class instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$isAlwaysTerminating = $result->isAlwaysTerminating();
			} else {
				$hasYield = false;
				$throwPoints = [];
				$impurePoints = [];
				$this->callNodeCallback($nodeCallback, $expr->class, $scope, $storage);
			}

			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			} else {
				$this->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);
			}
		} elseif ($expr instanceof Expr\Empty_) {
			$nonNullabilityResult = $this->ensureNonNullability($scope, $expr->expr);
			$scope = $this->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->expr);
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $this->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
			$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $expr->expr);
		} elseif ($expr instanceof Expr\Isset_) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$nonNullabilityResults = [];
			$isAlwaysTerminating = false;
			foreach ($expr->vars as $var) {
				$nonNullabilityResult = $this->ensureNonNullability($scope, $var);
				$scope = $this->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $var);
				$result = $this->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
				$nonNullabilityResults[] = $nonNullabilityResult;
			}
			foreach (array_reverse($expr->vars) as $var) {
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $var);
			}
			foreach (array_reverse($nonNullabilityResults) as $nonNullabilityResult) {
				$scope = $this->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
			}
		} elseif ($expr instanceof Instanceof_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			if ($expr->class instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			}
		} elseif ($expr instanceof List_) {
			// only in assign and foreach, processed elsewhere
			return new ExpressionResult($scope, $scope, false, false, [], []);
		} elseif ($expr instanceof New_) {
			$parametersAcceptor = null;
			$constructorReflection = null;
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			$className = null;
			$normalizedExpr = $expr;
			if ($expr->class instanceof Expr || $expr->class instanceof Name) {
				if ($expr->class instanceof Expr) {
					$objectClasses = $scope->getType($expr)->getObjectClassNames();
					if (count($objectClasses) === 1) {
						$objectExprResult = $this->processExprNode($stmt, new New_(new Name($objectClasses[0])), $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
						$className = $objectClasses[0];
						$additionalThrowPoints = $objectExprResult->getThrowPoints();
					} else {
						$additionalThrowPoints = [InternalThrowPoint::createImplicit($scope, $expr)];
					}

					$result = $this->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
					$scope = $result->getScope();
					$hasYield = $result->hasYield();
					$throwPoints = $result->getThrowPoints();
					$impurePoints = $result->getImpurePoints();
					$isAlwaysTerminating = $result->isAlwaysTerminating();
					foreach ($additionalThrowPoints as $throwPoint) {
						$throwPoints[] = $throwPoint;
					}
				} else {
					$className = $scope->resolveName($expr->class);
				}

				$classReflection = null;
				if ($className !== null && $this->reflectionProvider->hasClass($className)) {
					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasConstructor()) {
						$constructorReflection = $classReflection->getConstructor();
						$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
							$scope,
							$expr->getArgs(),
							$constructorReflection->getVariants(),
							$constructorReflection->getNamedArgumentsVariants(),
						);
						$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $classReflection, $expr, new Name\FullyQualified($className), $expr->getArgs(), $scope);
						if ($constructorThrowPoint !== null) {
							$throwPoints[] = $constructorThrowPoint;
						}
					}
				} else {
					$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
				}

				if ($constructorReflection !== null) {
					if (!$constructorReflection->hasSideEffects()->no()) {
						$certain = $constructorReflection->isPure()->no();
						$impurePoints[] = new ImpurePoint(
							$scope,
							$expr,
							'new',
							sprintf('instantiation of class %s', $constructorReflection->getDeclaringClass()->getDisplayName()),
							$certain,
						);
					}
				} elseif ($classReflection === null) {
					$impurePoints[] = new ImpurePoint(
						$scope,
						$expr,
						'new',
						'instantiation of unknown class',
						false,
					);
				}

				if ($parametersAcceptor !== null) {
					$normalizedExpr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
				}

			} else {
				$classReflection = $this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope); // populates $expr->class->name
				if ($classReflection->hasConstructor()) {
					$constructorReflection = $classReflection->getConstructor();
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$constructorReflection->getVariants(),
						$constructorReflection->getNamedArgumentsVariants(),
					);

					if ($constructorReflection->getDeclaringClass()->getName() === $classReflection->getName()) {
						$constructorResult = null;
						$this->processStmtNode($expr->class, $scope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $classReflection, &$constructorResult): void {
							$nodeCallback($node, $scope);
							if (!$node instanceof MethodReturnStatementsNode) {
								return;
							}
							if ($constructorResult !== null) {
								return;
							}
							$currentClassReflection = $node->getClassReflection();
							if ($currentClassReflection->getName() !== $classReflection->getName()) {
								return;
							}
							if (!$currentClassReflection->hasConstructor()) {
								return;
							}
							if ($currentClassReflection->getConstructor()->getName() !== $node->getMethodReflection()->getName()) {
								return;
							}
							$constructorResult = $node;
						}, StatementContext::createTopLevel());
						if ($constructorResult !== null) {
							$throwPoints = array_map(static fn (ThrowPoint $point) => InternalThrowPoint::createFromPublic($point), $constructorResult->getStatementResult()->getThrowPoints());
							$impurePoints = $constructorResult->getImpurePoints();
						}
					} else {
						$this->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
						$declaringClass = $constructorReflection->getDeclaringClass();
						$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $classReflection, $expr, new Name\FullyQualified($declaringClass->getName()), $expr->getArgs(), $scope);
						if ($constructorThrowPoint !== null) {
							$throwPoints[] = $constructorThrowPoint;
						}

						if (!$constructorReflection->hasSideEffects()->no()) {
							$certain = $constructorReflection->isPure()->no();
							$impurePoints[] = new ImpurePoint(
								$scope,
								$expr,
								'new',
								sprintf('instantiation of class %s', $declaringClass->getDisplayName()),
								$certain,
							);
						}
					}
				} else {
					$this->processStmtNode($expr->class, $scope, $storage, $nodeCallback, StatementContext::createTopLevel());
				}
			}

			$result = $this->processArgs($stmt, $constructorReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallback, $context);
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
		} elseif (
			$expr instanceof Expr\PreInc
			|| $expr instanceof Expr\PostInc
			|| $expr instanceof Expr\PreDec
			|| $expr instanceof Expr\PostDec
		) {
			$result = $this->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();

			$newExpr = $expr;
			if ($expr instanceof Expr\PostInc) {
				$newExpr = new Expr\PreInc($expr->var);
			} elseif ($expr instanceof Expr\PostDec) {
				$newExpr = new Expr\PreDec($expr->var);
			}

			$scope = $this->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$expr->var,
				$newExpr,
				$nodeCallback,
			)->getScope();
		} elseif ($expr instanceof Ternary) {
			$ternaryCondResult = $this->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $context->enterDeep());
			$throwPoints = $ternaryCondResult->getThrowPoints();
			$impurePoints = $ternaryCondResult->getImpurePoints();
			$isAlwaysTerminating = $ternaryCondResult->isAlwaysTerminating();
			$ifTrueScope = $ternaryCondResult->getTruthyScope();
			$ifFalseScope = $ternaryCondResult->getFalseyScope();
			$ifTrueType = null;
			if ($expr->if !== null) {
				$ifResult = $this->processExprNode($stmt, $expr->if, $ifTrueScope, $storage, $nodeCallback, $context);
				$throwPoints = array_merge($throwPoints, $ifResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $ifResult->getImpurePoints());
				$ifTrueScope = $ifResult->getScope();
				$ifTrueType = $ifTrueScope->getType($expr->if);
			}

			$elseResult = $this->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$ifFalseScope = $elseResult->getScope();

			$condType = $scope->getType($expr->cond);
			if ($condType->isTrue()->yes()) {
				$finalScope = $ifTrueScope;
			} elseif ($condType->isFalse()->yes()) {
				$finalScope = $ifFalseScope;
			} else {
				if ($ifTrueType instanceof NeverType && $ifTrueType->isExplicit()) {
					$finalScope = $ifFalseScope;
				} else {
					$ifFalseType = $ifFalseScope->getType($expr->else);

					if ($ifFalseType instanceof NeverType && $ifFalseType->isExplicit()) {
						$finalScope = $ifTrueScope;
					} else {
						$finalScope = $ifTrueScope->mergeWith($ifFalseScope);
					}
				}
			}

			$result = new ExpressionResult(
				$finalScope,
				$scope,
				$ternaryCondResult->hasYield(),
				$isAlwaysTerminating,
				$throwPoints,
				$impurePoints,
				static fn (): MutatingScope => $finalScope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $finalScope->filterByFalseyValue($expr),
			);
			$this->storeResult($storage, $expr, $result);
			return $result;

		} elseif ($expr instanceof Expr\Yield_) {
			$throwPoints = [
				InternalThrowPoint::createImplicit($scope, $expr),
			];
			$impurePoints = [
				new ImpurePoint(
					$scope,
					$expr,
					'yield',
					'yield',
					true,
				),
			];
			$isAlwaysTerminating = false;
			if ($expr->key !== null) {
				$keyResult = $this->processExprNode($stmt, $expr->key, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $keyResult->getScope();
				$throwPoints = $keyResult->getThrowPoints();
				$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
				$isAlwaysTerminating = $keyResult->isAlwaysTerminating();
			}
			if ($expr->value !== null) {
				$valueResult = $this->processExprNode($stmt, $expr->value, $scope, $storage, $nodeCallback, $context->enterDeep());
				$scope = $valueResult->getScope();
				$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
			}
			$hasYield = true;
		} elseif ($expr instanceof Expr\Match_) {
			$deepContext = $context->enterDeep();
			$condType = $scope->getType($expr->cond);
			$condResult = $this->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $deepContext);
			$scope = $condResult->getScope();
			$hasYield = $condResult->hasYield();
			$throwPoints = $condResult->getThrowPoints();
			$impurePoints = $condResult->getImpurePoints();
			$isAlwaysTerminating = $condResult->isAlwaysTerminating();
			$matchScope = $scope->enterMatch($expr);
			$armNodes = [];
			$hasDefaultCond = false;
			$hasAlwaysTrueCond = false;
			$arms = $expr->arms;
			$armCondsToSkip = [];
			if ($condType->isEnum()->yes()) {
				// enum match analysis would work even without this if branch
				// but would be much slower
				// this avoids using ObjectType::$subtractedType which is slow for huge enums
				// because of repeated union type normalization
				$enumCases = $condType->getEnumCases();
				if (count($enumCases) > 0) {
					$indexedEnumCases = [];
					foreach ($enumCases as $enumCase) {
						$indexedEnumCases[strtolower($enumCase->getClassName())][$enumCase->getEnumCaseName()] = $enumCase;
					}
					$unusedIndexedEnumCases = $indexedEnumCases;
					foreach ($arms as $i => $arm) {
						if ($arm->conds === null) {
							continue;
						}

						$condNodes = [];
						$conditionCases = [];
						$conditionExprs = [];
						foreach ($arm->conds as $j => $cond) {
							if (!$cond instanceof Expr\ClassConstFetch) {
								continue 2;
							}
							if (!$cond->class instanceof Name) {
								continue 2;
							}
							if (!$cond->name instanceof Node\Identifier) {
								continue 2;
							}
							$fetchedClassName = $scope->resolveName($cond->class);
							$loweredFetchedClassName = strtolower($fetchedClassName);
							if (!array_key_exists($loweredFetchedClassName, $indexedEnumCases)) {
								continue 2;
							}

							if (!array_key_exists($loweredFetchedClassName, $unusedIndexedEnumCases)) {
								throw new ShouldNotHappenException();
							}

							$caseName = $cond->name->toString();
							if (!array_key_exists($caseName, $indexedEnumCases[$loweredFetchedClassName])) {
								continue 2;
							}

							$enumCase = $indexedEnumCases[$loweredFetchedClassName][$caseName];
							$conditionCases[] = $enumCase;
							$armConditionScope = $matchScope;
							if (!array_key_exists($caseName, $unusedIndexedEnumCases[$loweredFetchedClassName])) {
								// force "always false"
								$armConditionScope = $armConditionScope->removeTypeFromExpression(
									$expr->cond,
									$enumCase,
								);
							} else {
								$unusedCasesCount = 0;
								foreach ($unusedIndexedEnumCases as $cases) {
									$unusedCasesCount += count($cases);
								}
								if ($unusedCasesCount === 1) {
									$hasAlwaysTrueCond = true;

									// force "always true"
									$armConditionScope = $armConditionScope->addTypeToExpression(
										$expr->cond,
										$enumCase,
									);
								}
							}

							$this->processExprNode($stmt, $cond, $armConditionScope, $storage, $nodeCallback, $deepContext);

							$condNodes[] = new MatchExpressionArmCondition(
								$cond,
								$armConditionScope,
								$cond->getStartLine(),
							);
							$conditionExprs[] = $cond;

							unset($unusedIndexedEnumCases[$loweredFetchedClassName][$caseName]);
							$armCondsToSkip[$i][$j] = true;
						}

						$conditionCasesCount = count($conditionCases);
						if ($conditionCasesCount === 0) {
							throw new ShouldNotHappenException();
						} elseif ($conditionCasesCount === 1) {
							$conditionCaseType = $conditionCases[0];
						} else {
							$conditionCaseType = new UnionType($conditionCases);
						}

						$filteringExpr = $this->getFilteringExprForMatchArm($expr, $conditionExprs);
						$matchArmBodyScope = $matchScope->addTypeToExpression(
							$expr->cond,
							$conditionCaseType,
						)->filterByTruthyValue($filteringExpr);
						$matchArmBody = new MatchExpressionArmBody($matchArmBodyScope, $arm->body);
						$armNodes[$i] = new MatchExpressionArm($matchArmBody, $condNodes, $arm->getStartLine());

						$armResult = $this->processExprNode(
							$stmt,
							$arm->body,
							$matchArmBodyScope,
							$storage,
							$nodeCallback,
							ExpressionContext::createTopLevel(),
						);
						$armScope = $armResult->getScope();
						$scope = $scope->mergeWith($armScope);
						$hasYield = $hasYield || $armResult->hasYield();
						$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
						$impurePoints = array_merge($impurePoints, $armResult->getImpurePoints());

						unset($arms[$i]);
					}

					$remainingCases = [];
					foreach ($unusedIndexedEnumCases as $cases) {
						foreach ($cases as $case) {
							$remainingCases[] = $case;
						}
					}

					$remainingCasesCount = count($remainingCases);
					if ($remainingCasesCount === 0) {
						$remainingType = new NeverType();
					} elseif ($remainingCasesCount === 1) {
						$remainingType = $remainingCases[0];
					} else {
						$remainingType = new UnionType($remainingCases);
					}

					$matchScope = $matchScope->addTypeToExpression($expr->cond, $remainingType);
				}
			}
			foreach ($arms as $i => $arm) {
				if ($arm->conds === null) {
					$hasDefaultCond = true;
					$matchArmBody = new MatchExpressionArmBody($matchScope, $arm->body);
					$armNodes[$i] = new MatchExpressionArm($matchArmBody, [], $arm->getStartLine());
					$armResult = $this->processExprNode($stmt, $arm->body, $matchScope, $storage, $nodeCallback, ExpressionContext::createTopLevel());
					$matchScope = $armResult->getScope();
					$hasYield = $hasYield || $armResult->hasYield();
					$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $armResult->getImpurePoints());
					$scope = $scope->mergeWith($matchScope);
					continue;
				}

				if (count($arm->conds) === 0) {
					throw new ShouldNotHappenException();
				}

				$filteringExprs = [];
				$armCondScope = $matchScope;
				$condNodes = [];
				foreach ($arm->conds as $j => $armCond) {
					if (isset($armCondsToSkip[$i][$j])) {
						continue;
					}
					$condNodes[] = new MatchExpressionArmCondition($armCond, $armCondScope, $armCond->getStartLine());
					$armCondResult = $this->processExprNode($stmt, $armCond, $armCondScope, $storage, $nodeCallback, $deepContext);
					$hasYield = $hasYield || $armCondResult->hasYield();
					$throwPoints = array_merge($throwPoints, $armCondResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $armCondResult->getImpurePoints());
					$armCondExpr = new BinaryOp\Identical($expr->cond, $armCond);
					$armCondResultScope = $armCondResult->getScope();
					$armCondType = $this->treatPhpDocTypesAsCertain ? $armCondResultScope->getType($armCondExpr) : $armCondResultScope->getNativeType($armCondExpr);
					if ($armCondType->isTrue()->yes()) {
						$hasAlwaysTrueCond = true;
					}
					$armCondScope = $armCondResult->getScope()->filterByFalseyValue($armCondExpr);
					$filteringExprs[] = $armCond;
				}

				$filteringExpr = $this->getFilteringExprForMatchArm($expr, $filteringExprs);
				$bodyScope = $matchScope->filterByTruthyValue($filteringExpr);
				$matchArmBody = new MatchExpressionArmBody($bodyScope, $arm->body);
				$armNodes[$i] = new MatchExpressionArm($matchArmBody, $condNodes, $arm->getStartLine());

				$armResult = $this->processExprNode(
					$stmt,
					$arm->body,
					$bodyScope,
					$storage,
					$nodeCallback,
					ExpressionContext::createTopLevel(),
				);
				$armScope = $armResult->getScope();
				$scope = $scope->mergeWith($armScope);
				$hasYield = $hasYield || $armResult->hasYield();
				$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $armResult->getImpurePoints());
				$matchScope = $matchScope->filterByFalseyValue($filteringExpr);
			}

			$remainingType = $matchScope->getType($expr->cond);
			if (!$hasDefaultCond && !$hasAlwaysTrueCond && !$remainingType instanceof NeverType) {
				$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(UnhandledMatchError::class), $expr, false);
			}

			ksort($armNodes, SORT_NUMERIC);

			$this->callNodeCallback($nodeCallback, new MatchExpressionNode($expr->cond, array_values($armNodes), $expr, $matchScope), $scope, $storage);
		} elseif ($expr instanceof AlwaysRememberedExpr) {
			$result = $this->processExprNode($stmt, $expr->getExpr(), $scope, $storage, $nodeCallback, $context);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
		} elseif ($expr instanceof Expr\Throw_) {
			$hasYield = false;
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, $scope->getType($expr->expr), $expr, false);
		} elseif ($expr instanceof FunctionCallableNode) {
			$throwPoints = [];
			$impurePoints = [];
			$hasYield = false;
			$isAlwaysTerminating = false;
			if ($expr->getName() instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$impurePoints = $result->getImpurePoints();
				$isAlwaysTerminating = $result->isAlwaysTerminating();
			}
		} elseif ($expr instanceof MethodCallableNode) {
			$result = $this->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = false;
			if ($expr->getName() instanceof Expr) {
				$nameResult = $this->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $nameResult->getScope();
				$hasYield = $hasYield || $nameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
				$isAlwaysTerminating = $nameResult->isAlwaysTerminating();
			}
		} elseif ($expr instanceof StaticMethodCallableNode) {
			$throwPoints = [];
			$impurePoints = [];
			$hasYield = false;
			$isAlwaysTerminating = false;
			if ($expr->getClass() instanceof Expr) {
				$classResult = $this->processExprNode($stmt, $expr->getClass(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $classResult->getScope();
				$hasYield = $classResult->hasYield();
				$throwPoints = $classResult->getThrowPoints();
				$impurePoints = $classResult->getImpurePoints();
				$isAlwaysTerminating = $classResult->isAlwaysTerminating();
			}
			if ($expr->getName() instanceof Expr) {
				$nameResult = $this->processExprNode($stmt, $expr->getName(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $nameResult->getScope();
				$hasYield = $hasYield || $nameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
			}
		} elseif ($expr instanceof InstantiationCallableNode) {
			$throwPoints = [];
			$impurePoints = [];
			$hasYield = false;
			$isAlwaysTerminating = false;
			if ($expr->getClass() instanceof Expr) {
				$classResult = $this->processExprNode($stmt, $expr->getClass(), $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
				$scope = $classResult->getScope();
				$hasYield = $classResult->hasYield();
				$throwPoints = $classResult->getThrowPoints();
				$impurePoints = $classResult->getImpurePoints();
				$isAlwaysTerminating = $classResult->isAlwaysTerminating();
			}
		} elseif ($expr instanceof Node\Scalar) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
		} elseif ($expr instanceof ConstFetch) {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
			$this->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);
		} else {
			$hasYield = false;
			$throwPoints = [];
			$impurePoints = [];
			$isAlwaysTerminating = false;
		}

		$result = new ExpressionResult(
			$scope,
			$originalScope,
			$hasYield,
			$isAlwaysTerminating,
			$throwPoints,
			$impurePoints,
			static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
		$this->storeResult($storage, $expr, $result);
		return $result;
	}

	private function getArrayFunctionAppendingType(FunctionReflection $functionReflection, Scope $scope, FuncCall $expr): Type
	{
		$arrayArg = $expr->getArgs()[0]->value;
		$arrayType = $scope->getType($arrayArg);
		$callArgs = array_slice($expr->getArgs(), 1);

		/**
		 * @param Arg[] $callArgs
		 * @param callable(?Type, Type, bool): void $setOffsetValueType
		 */
		$setOffsetValueTypes = static function (Scope $scope, array $callArgs, callable $setOffsetValueType, ?bool &$nonConstantArrayWasUnpacked = null): void {
			foreach ($callArgs as $callArg) {
				$callArgType = $scope->getType($callArg->value);
				if ($callArg->unpack) {
					$constantArrays = $callArgType->getConstantArrays();
					if (count($constantArrays) === 1) {
						$iterableValueTypes = $constantArrays[0]->getValueTypes();
					} else {
						$iterableValueTypes = [$callArgType->getIterableValueType()];
						$nonConstantArrayWasUnpacked = true;
					}

					$isOptional = !$callArgType->isIterableAtLeastOnce()->yes();
					foreach ($iterableValueTypes as $iterableValueType) {
						if ($iterableValueType instanceof UnionType) {
							foreach ($iterableValueType->getTypes() as $innerType) {
								$setOffsetValueType(null, $innerType, $isOptional);
							}
						} else {
							$setOffsetValueType(null, $iterableValueType, $isOptional);
						}
					}
					continue;
				}
				$setOffsetValueType(null, $callArgType, false);
			}
		};

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$newArrayTypes = [];
			$prepend = $functionReflection->getName() === 'array_unshift';
			foreach ($constantArrays as $constantArray) {
				$arrayTypeBuilder = $prepend ? ConstantArrayTypeBuilder::createEmpty() : ConstantArrayTypeBuilder::createFromConstantArray($constantArray);

				$setOffsetValueTypes(
					$scope,
					$callArgs,
					static function (?Type $offsetType, Type $valueType, bool $optional) use (&$arrayTypeBuilder): void {
						$arrayTypeBuilder->setOffsetValueType($offsetType, $valueType, $optional);
					},
					$nonConstantArrayWasUnpacked,
				);

				if ($prepend) {
					$keyTypes = $constantArray->getKeyTypes();
					$valueTypes = $constantArray->getValueTypes();
					foreach ($keyTypes as $k => $keyType) {
						$arrayTypeBuilder->setOffsetValueType(
							count($keyType->getConstantStrings()) === 1 ? $keyType->getConstantStrings()[0] : null,
							$valueTypes[$k],
							$constantArray->isOptionalKey($k),
						);
					}
				}

				$constantArray = $arrayTypeBuilder->getArray();

				if ($constantArray->isConstantArray()->yes() && $nonConstantArrayWasUnpacked) {
					$array = new ArrayType($constantArray->generalize(GeneralizePrecision::lessSpecific())->getIterableKeyType(), $constantArray->getIterableValueType());
					$isList = $constantArray->isList()->yes();
					$constantArray = $constantArray->isIterableAtLeastOnce()->yes()
						? TypeCombinator::intersect($array, new NonEmptyArrayType())
						: $array;
					$constantArray = $isList
						? TypeCombinator::intersect($constantArray, new AccessoryArrayListType())
						: $constantArray;
				}

				$newArrayTypes[] = $constantArray;
			}

			return TypeCombinator::union(...$newArrayTypes);
		}

		$setOffsetValueTypes(
			$scope,
			$callArgs,
			static function (?Type $offsetType, Type $valueType, bool $optional) use (&$arrayType): void {
				$isIterableAtLeastOnce = $arrayType->isIterableAtLeastOnce()->yes() || !$optional;
				$arrayType = $arrayType->setOffsetValueType($offsetType, $valueType);
				if ($isIterableAtLeastOnce) {
					return;
				}

				$arrayType = TypeCombinator::union($arrayType, new ConstantArrayType([], []));
			},
		);

		return $arrayType;
	}

	private function getArraySortPreserveListFunctionType(Type $type): Type
	{
		$isIterableAtLeastOnce = $type->isIterableAtLeastOnce();
		if ($isIterableAtLeastOnce->no()) {
			return $type;
		}

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($isIterableAtLeastOnce): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}

			if (!$type instanceof ArrayType && !$type instanceof ConstantArrayType) {
				return $type;
			}

			$newArrayType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $type->getIterableValueType()), new AccessoryArrayListType());
			if ($isIterableAtLeastOnce->yes()) {
				$newArrayType = TypeCombinator::intersect($newArrayType, new NonEmptyArrayType());
			}

			return $newArrayType;
		});
	}

	private function getArraySortDoNotPreserveListFunctionType(Type $type): Type
	{
		$isIterableAtLeastOnce = $type->isIterableAtLeastOnce();
		if ($isIterableAtLeastOnce->no()) {
			return $type;
		}

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($isIterableAtLeastOnce): Type {
			if ($type instanceof UnionType) {
				return $traverse($type);
			}

			$constantArrays = $type->getConstantArrays();
			if (count($constantArrays) > 0) {
				$types = [];
				foreach ($constantArrays as $constantArray) {
					$types[] = new ConstantArrayType(
						$constantArray->getKeyTypes(),
						$constantArray->getValueTypes(),
						$constantArray->getNextAutoIndexes(),
						$constantArray->getOptionalKeys(),
						$constantArray->isList()->and(TrinaryLogic::createMaybe()),
					);
				}

				return TypeCombinator::union(...$types);
			}

			$newArrayType = new ArrayType($type->getIterableKeyType(), $type->getIterableValueType());
			if ($isIterableAtLeastOnce->yes()) {
				$newArrayType = TypeCombinator::intersect($newArrayType, new NonEmptyArrayType());
			}

			return $newArrayType;
		});
	}

	private function getFunctionThrowPoint(
		FunctionReflection $functionReflection,
		?ParametersAcceptor $parametersAcceptor,
		FuncCall $funcCall,
		MutatingScope $scope,
	): ?InternalThrowPoint
	{
		$normalizedFuncCall = $funcCall;
		if ($parametersAcceptor !== null) {
			$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $funcCall);
		}

		if ($normalizedFuncCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicFunctionThrowTypeExtensions() as $extension) {
				if (!$extension->isFunctionSupported($functionReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromFunctionCall($functionReflection, $normalizedFuncCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $funcCall, false);
			}
		}

		$throwType = $functionReflection->getThrowType();
		if ($throwType === null && $parametersAcceptor !== null) {
			$returnType = $parametersAcceptor->getReturnType();
			if ($returnType instanceof NeverType && $returnType->isExplicit()) {
				$throwType = new ObjectType(Throwable::class);
			}
		}

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $funcCall, true);
			}
		} elseif ($this->implicitThrows) {
			$requiredParameters = null;
			if ($parametersAcceptor !== null) {
				$requiredParameters = 0;
				foreach ($parametersAcceptor->getParameters() as $parameter) {
					if ($parameter->isOptional()) {
						continue;
					}

					$requiredParameters++;
				}
			}
			if (
				!$functionReflection->isBuiltin()
				|| $requiredParameters === null
				|| $requiredParameters > 0
				|| count($funcCall->getArgs()) > 0
			) {
				$functionReturnedType = $scope->getType($funcCall);
				if (!(new ObjectType(Throwable::class))->isSuperTypeOf($functionReturnedType)->yes()) {
					return InternalThrowPoint::createImplicit($scope, $funcCall);
				}
			}
		}

		return null;
	}

	private function getMethodThrowPoint(MethodReflection $methodReflection, ParametersAcceptor $parametersAcceptor, MethodCall $methodCall, MutatingScope $scope): ?InternalThrowPoint
	{
		$normalizedMethodCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isMethodSupported($methodReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromMethodCall($methodReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, false);
			}
		}

		$throwType = $methodReflection->getThrowType();
		if ($throwType === null) {
			$returnType = $parametersAcceptor->getReturnType();
			if ($returnType instanceof NeverType && $returnType->isExplicit()) {
				$throwType = new ObjectType(Throwable::class);
			}
		}

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $scope->getType($methodCall);
			if (!(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	/**
	 * @param list<Node\Arg> $args
	 */
	private function getConstructorThrowPoint(MethodReflection $constructorReflection, ParametersAcceptor $parametersAcceptor, ClassReflection $classReflection, New_ $new, Name $className, array $args, MutatingScope $scope): ?InternalThrowPoint
	{
		$methodCall = new StaticCall($className, $constructorReflection->getName(), $args);
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicStaticMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isStaticMethodSupported($constructorReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromStaticMethodCall($constructorReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $new, false);
			}
		}

		if ($constructorReflection->getThrowType() !== null) {
			$throwType = $constructorReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $new, true);
			}
		} elseif ($this->implicitThrows) {
			if (!$classReflection->is(Throwable::class)) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	private function getStaticMethodThrowPoint(MethodReflection $methodReflection, ParametersAcceptor $parametersAcceptor, StaticCall $methodCall, MutatingScope $scope): ?InternalThrowPoint
	{
		$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		if ($normalizedMethodCall !== null) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicStaticMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isStaticMethodSupported($methodReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromStaticMethodCall($methodReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, false);
			}
		}

		if ($methodReflection->getThrowType() !== null) {
			$throwType = $methodReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $methodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $scope->getType($methodCall);
			if (!(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return InternalThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	private function getPropertyReadThrowPointsFromGetHook(
		MutatingScope $scope,
		PropertyFetch $propertyFetch,
		PhpPropertyReflection $propertyReflection,
	): array
	{
		return $this->getThrowPointsFromPropertyHook($scope, $propertyFetch, $propertyReflection, 'get');
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	private function getPropertyAssignThrowPointsFromSetHook(
		MutatingScope $scope,
		PropertyFetch $propertyFetch,
		PhpPropertyReflection $propertyReflection,
	): array
	{
		return $this->getThrowPointsFromPropertyHook($scope, $propertyFetch, $propertyReflection, 'set');
	}

	/**
	 * @param 'get'|'set' $hookName
	 * @return InternalThrowPoint[]
	 */
	private function getThrowPointsFromPropertyHook(
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
	private function getAssignedVariables(Expr $expr): array
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
	private function callNodeCallbackWithExpression(
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
	protected function callNodeCallback(
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
	private function processClosureNode(
		Node\Stmt $stmt,
		Expr\Closure $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
	): ProcessClosureResult
	{
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}

		$byRefUses = [];

		$closureCallArgs = $expr->getAttribute(ClosureArgVisitor::ATTRIBUTE_NAME);
		$callableParameters = $this->createCallableParameters(
			$scope,
			$expr,
			$closureCallArgs,
			$passedToType,
		);

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

		$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters);
		$closureScope = $closureScope->processClosureScope($scope, null, $byRefUses);
		$closureType = $closureScope->getAnonymousFunctionReflection();
		if (!$closureType instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$returnType = $closureType->getReturnType();
		$isAlwaysTerminating = ($returnType instanceof NeverType && $returnType->isExplicit());

		$this->callNodeCallback($nodeCallback, new InClosureNode($closureType, $expr), $closureScope, $storage);

		$executionEnds = [];
		$gatheredReturnStatements = [];
		$gatheredYieldStatements = [];
		$closureImpurePoints = [];
		$invalidateExpressions = [];
		$closureStmtsCallback = static function (Node $node, Scope $scope) use ($nodeCallback, &$executionEnds, &$gatheredReturnStatements, &$gatheredYieldStatements, &$closureScope, &$closureImpurePoints, &$invalidateExpressions): void {
			$nodeCallback($node, $scope);
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
		};

		if (count($byRefUses) === 0) {
			$statementResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, $closureStmtsCallback, StatementContext::createTopLevel());
			$publicStatementResult = $statementResult->toPublic();
			$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$publicStatementResult,
				$executionEnds,
				array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
			), $closureScope, $storage);

			return new ProcessClosureResult($scope, $statementResult->getThrowPoints(), $statementResult->getImpurePoints(), $invalidateExpressions, $isAlwaysTerminating);
		}

		$originalStorage = $storage;

		$count = 0;
		$closureResultScope = null;
		do {
			$prevScope = $closureScope;

			$storage = $originalStorage->duplicate();
			$intermediaryClosureScopeResult = $this->processStmtNodesInternal($expr, $expr->stmts, $closureScope, $storage, new NoopNodeCallback(), StatementContext::createTopLevel());
			$intermediaryClosureScope = $intermediaryClosureScopeResult->getScope();
			foreach ($intermediaryClosureScopeResult->getExitPoints() as $exitPoint) {
				$intermediaryClosureScope = $intermediaryClosureScope->mergeWith($exitPoint->getScope());
			}

			if ($expr->getAttribute(ImmediatelyInvokedClosureVisitor::ATTRIBUTE_NAME) === true) {
				$closureResultScope = $intermediaryClosureScope;
				break;
			}

			$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters);
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
		$this->callNodeCallback($nodeCallback, new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredYieldStatements,
			$publicStatementResult,
			$executionEnds,
			array_merge($publicStatementResult->getImpurePoints(), $closureImpurePoints),
		), $closureScope, $storage);

		return new ProcessClosureResult($scope->processClosureScope($closureResultScope, null, $byRefUses), $statementResult->getThrowPoints(), $statementResult->getImpurePoints(), $invalidateExpressions, $isAlwaysTerminating);
	}

	/**
	 * @param InvalidateExprNode[] $invalidatedExpressions
	 * @param string[] $uses
	 */
	private function processImmediatelyCalledCallable(MutatingScope $scope, array $invalidatedExpressions, array $uses): MutatingScope
	{
		if ($scope->isInClass()) {
			$uses[] = 'this';
		}

		$finder = new NodeFinder();
		foreach ($invalidatedExpressions as $invalidateExpression) {
			$found = false;
			foreach ($uses as $use) {
				$result = $finder->findFirst([$invalidateExpression->getExpr()], static fn ($node) => $node instanceof Variable && $node->name === $use);
				if ($result === null) {
					continue;
				}

				$found = true;
				break;
			}

			if (!$found) {
				continue;
			}

			$scope = $scope->invalidateExpression($invalidateExpression->getExpr(), true);
		}

		return $scope;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processArrowFunctionNode(
		Node\Stmt $stmt,
		Expr\ArrowFunction $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		?Type $passedToType,
	): ExpressionResult
	{
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $storage, $nodeCallback);
		}
		if ($expr->returnType !== null) {
			$this->callNodeCallback($nodeCallback, $expr->returnType, $scope, $storage);
		}

		$arrowFunctionCallArgs = $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME);
		$arrowFunctionScope = $scope->enterArrowFunction($expr, $this->createCallableParameters(
			$scope,
			$expr,
			$arrowFunctionCallArgs,
			$passedToType,
		));
		$arrowFunctionType = $arrowFunctionScope->getAnonymousFunctionReflection();
		if ($arrowFunctionType === null) {
			throw new ShouldNotHappenException();
		}
		$this->callNodeCallback($nodeCallback, new InArrowFunctionNode($arrowFunctionType, $expr), $arrowFunctionScope, $storage);
		$exprResult = $this->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $storage, $nodeCallback, ExpressionContext::createTopLevel());

		return new ExpressionResult($scope, $scope, false, $exprResult->isAlwaysTerminating(), $exprResult->getThrowPoints(), $exprResult->getImpurePoints());
	}

	/**
	 * @param Node\Arg[] $args
	 * @return ParameterReflection[]|null
	 */
	public function createCallableParameters(Scope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType): ?array
	{
		$callableParameters = null;
		if ($args !== null) {
			$closureType = $scope->getType($closureExpr);

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

					$type = $scope->getType($args[$index]->value);
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
			if (count($acceptors) > 0) {
				foreach ($acceptors as $acceptor) {
					if ($callableParameters === null) {
						$callableParameters = array_map(static fn (ParameterReflection $callableParameter) => new NativeParameterReflection(
							$callableParameter->getName(),
							$callableParameter->isOptional(),
							$callableParameter->getType(),
							$callableParameter->passedByReference(),
							$callableParameter->isVariadic(),
							$callableParameter->getDefaultValue(),
						), $acceptor->getParameters());
						continue;
					}

					$newParameters = [];
					foreach ($acceptor->getParameters() as $i => $callableParameter) {
						if (!array_key_exists($i, $callableParameters)) {
							$newParameters[] = $callableParameter;
							continue;
						}

						$newParameters[] = $callableParameters[$i]->union(new NativeParameterReflection(
							$callableParameter->getName(),
							$callableParameter->isOptional(),
							$callableParameter->getType(),
							$callableParameter->passedByReference(),
							$callableParameter->isVariadic(),
							$callableParameter->getDefaultValue(),
						));
					}

					$callableParameters = $newParameters;
				}
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

			[, $phpDocParameterTypes,,,, $phpDocThrowType,,,,,,,, $phpDocComment] = $this->getPhpDocs($scope, $hook);

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
			$statementResult = $this->processStmtNodesInternal(new PropertyHookStatementNode($hook), $stmts, $hookScope, $storage, static function (Node $node, Scope $scope) use ($nodeCallback, $hookScope, &$gatheredReturnStatements, &$executionEnds, &$hookImpurePoints): void {
				$nodeCallback($node, $scope);
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
			}, StatementContext::createTopLevel())->toPublic();

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
			foreach ($this->parameterClosureThisExtensionProvider->getFunctionParameterClosureThisExtensions() as $extension) {
				if (! $extension->isFunctionSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromFunctionCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		} elseif ($call instanceof StaticCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->parameterClosureThisExtensionProvider->getStaticMethodParameterClosureThisExtensions() as $extension) {
				if (! $extension->isStaticMethodSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromStaticMethodCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		} elseif ($call instanceof MethodCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->parameterClosureThisExtensionProvider->getMethodParameterClosureThisExtensions() as $extension) {
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
	private function processArgs(
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

		if ($parametersAcceptor !== null) {
			$parameters = $parametersAcceptor->getParameters();
		}

		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($args as $i => $arg) {
			$assignByReference = false;
			$parameter = null;
			$parameterType = null;
			$parameterNativeType = null;
			if (isset($parameters) && $parametersAcceptor !== null) {
				if (isset($parameters[$i])) {
					$assignByReference = $parameters[$i]->passedByReference()->createsNewVariable();
					$parameterType = $parameters[$i]->getType();

					if ($parameters[$i] instanceof ExtendedParameterReflection) {
						$parameterNativeType = $parameters[$i]->getNativeType();
					}
					$parameter = $parameters[$i];
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

			if ($calleeReflection !== null) {
				$scope = $scope->pushInFunctionCall($calleeReflection, $parameter);
			}

			$originalArg = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
			$this->callNodeCallback($nodeCallback, $originalArg, $scope, $storage);

			$originalScope = $scope;
			$scopeToPass = $scope;
			if ($i === 0 && $closureBindScope !== null) {
				$scopeToPass = $closureBindScope;
			}

			$parameterCallableType = null;
			if ($parameterType !== null) {
				$parameterCallableType = TypeUtils::findCallableType($parameterType);
			}

			if ($parameter instanceof ExtendedParameterReflection) {
				$parameterCallImmediately = $parameter->isImmediatelyInvokedCallable();
				if ($parameterCallImmediately->maybe()) {
					$callCallbackImmediately = $parameterCallableType !== null && $calleeReflection instanceof FunctionReflection;
				} else {
					$callCallbackImmediately = $parameterCallImmediately->yes();
				}
			} else {
				$callCallbackImmediately = $parameterCallableType !== null && $calleeReflection instanceof FunctionReflection;
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
						$scopeToPass = $scopeToPass->assignVariable('this', $closureThisType, new ObjectWithoutClassType(), TrinaryLogic::createYes());
					}
				}

				if ($parameter !== null) {
					$overwritingParameterType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass);

					if ($overwritingParameterType !== null) {
						$parameterType = $overwritingParameterType;
					}
				}

				$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
				$closureResult = $this->processClosureNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context, $parameterType ?? null);
				if ($callCallbackImmediately) {
					$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $closureResult->getThrowPoints()));
					$impurePoints = array_merge($impurePoints, $closureResult->getImpurePoints());
					$isAlwaysTerminating = $isAlwaysTerminating || $closureResult->isAlwaysTerminating();
				}

				$this->storeResult($storage, $arg->value, new ExpressionResult($closureResult->getScope(), $scopeToPass, false, $isAlwaysTerminating, $throwPoints, $impurePoints));

				$uses = [];
				foreach ($arg->value->uses as $use) {
					if (!is_string($use->var->name)) {
						continue;
					}

					$uses[] = $use->var->name;
				}

				$scope = $closureResult->getScope();
				$invalidateExpressions = $closureResult->getInvalidateExpressions();
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

				$scope = $this->processImmediatelyCalledCallable($scope, $invalidateExpressions, $uses);
			} elseif ($arg->value instanceof Expr\ArrowFunction) {
				if (
					$closureBindScope === null
					&& $parameter instanceof ExtendedParameterReflection
					&& !$arg->value->static
				) {
					$closureThisType = $this->resolveClosureThisType($callLike, $calleeReflection, $parameter, $scopeToPass);
					if ($closureThisType !== null) {
						$scopeToPass = $scopeToPass->assignVariable('this', $closureThisType, new ObjectWithoutClassType(), TrinaryLogic::createYes());
					}
				}

				if ($parameter !== null) {
					$overwritingParameterType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass);

					if ($overwritingParameterType !== null) {
						$parameterType = $overwritingParameterType;
					}
				}

				$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
				$arrowFunctionResult = $this->processArrowFunctionNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $parameterType ?? null);
				if ($callCallbackImmediately) {
					$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $arrowFunctionResult->getThrowPoints()));
					$impurePoints = array_merge($impurePoints, $arrowFunctionResult->getImpurePoints());
					$isAlwaysTerminating = $isAlwaysTerminating || $arrowFunctionResult->isAlwaysTerminating();
				}
				$this->storeResult($storage, $arg->value, new ExpressionResult($arrowFunctionResult->getScope(), $scopeToPass, false, $isAlwaysTerminating, $throwPoints, $impurePoints));
			} else {
				$exprType = $scope->getType($arg->value);
				$exprResult = $this->processExprNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context->enterDeep());
				$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $exprResult->isAlwaysTerminating();
				$scope = $exprResult->getScope();
				$hasYield = $hasYield || $exprResult->hasYield();

				if ($exprType->isCallable()->yes()) {
					$acceptors = $exprType->getCallableParametersAcceptors($scope);
					if (count($acceptors) === 1) {
						$scope = $this->processImmediatelyCalledCallable($scope, $acceptors[0]->getInvalidateExpressions(), $acceptors[0]->getUsedVariables());
						if ($callCallbackImmediately) {
							$callableThrowPoints = array_map(static fn (SimpleThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable()) : InternalThrowPoint::createImplicit($scope, $arg->value), $acceptors[0]->getThrowPoints());
							if (!$this->implicitThrows) {
								$callableThrowPoints = array_values(array_filter($callableThrowPoints, static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit()));
							}
							$throwPoints = array_merge($throwPoints, $callableThrowPoints);
							$impurePoints = array_merge($impurePoints, array_map(static fn (SimpleImpurePoint $impurePoint) => new ImpurePoint($scope, $arg->value, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $acceptors[0]->getImpurePoints()));
							$returnType = $acceptors[0]->getReturnType();
							$isAlwaysTerminating = $isAlwaysTerminating || ($returnType instanceof NeverType && $returnType->isExplicit());
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
		foreach ($args as $i => $arg) {
			if (!isset($parameters) || $parametersAcceptor === null) {
				continue;
			}

			$byRefType = new MixedType();
			$assignByReference = false;
			$currentParameter = null;
			if (isset($parameters[$i])) {
				$currentParameter = $parameters[$i];
			} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
				$currentParameter = array_last($parameters);
			}

			if ($currentParameter !== null) {
				$assignByReference = $currentParameter->passedByReference()->createsNewVariable();
				if ($assignByReference) {
					if ($currentParameter instanceof ExtendedParameterReflection && $currentParameter->getOutType() !== null) {
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
					}
				}
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

		// not storing this, it's scope after processing all args
		return new ExpressionResult($scope, $scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function getParameterTypeFromParameterClosureTypeExtension(CallLike $callLike, $calleeReflection, ParameterReflection $parameter, MutatingScope $scope): ?Type
	{
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->parameterClosureTypeExtensionProvider->getFunctionParameterClosureTypeExtensions() as $functionParameterClosureTypeExtension) {
				if ($functionParameterClosureTypeExtension->isFunctionSupported($calleeReflection, $parameter)) {
					return $functionParameterClosureTypeExtension->getTypeFromFunctionCall($calleeReflection, $callLike, $parameter, $scope);
				}
			}
		} elseif ($calleeReflection instanceof MethodReflection) {
			if ($callLike instanceof StaticCall) {
				foreach ($this->parameterClosureTypeExtensionProvider->getStaticMethodParameterClosureTypeExtensions() as $staticMethodParameterClosureTypeExtension) {
					if ($staticMethodParameterClosureTypeExtension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $staticMethodParameterClosureTypeExtension->getTypeFromStaticMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof MethodCall) {
				foreach ($this->parameterClosureTypeExtensionProvider->getMethodParameterClosureTypeExtensions() as $methodParameterClosureTypeExtension) {
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
			foreach ($this->parameterOutTypeExtensionProvider->getFunctionParameterOutTypeExtensions() as $functionParameterOutTypeExtension) {
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
			foreach ($this->parameterOutTypeExtensionProvider->getMethodParameterOutTypeExtensions() as $methodParameterOutTypeExtension) {
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
			foreach ($this->parameterOutTypeExtensionProvider->getStaticMethodParameterOutTypeExtensions() as $staticMethodParameterOutTypeExtension) {
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
	 * @param Closure(MutatingScope $scope): ExpressionResult $processExprCallback
	 */
	private function processAssignVar(
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		Node\Stmt $stmt,
		Expr $var,
		Expr $assignedExpr,
		callable $nodeCallback,
		ExpressionContext $context,
		Closure $processExprCallback,
		bool $enterExpressionAssign,
	): ExpressionResult
	{
		$originalScope = $scope;
		$this->callNodeCallback($nodeCallback, $var, $enterExpressionAssign ? $scope->enterExpressionAssign($var) : $scope, $storage);
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$isAssignOp = $assignedExpr instanceof Expr\AssignOp && !$enterExpressionAssign;
		if ($var instanceof Variable && is_string($var->name)) {
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			if (in_array($var->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
				$impurePoints[] = new ImpurePoint($scope, $var, 'superglobal', 'assign to superglobal variable', true);
			}
			$assignedExpr = $this->unwrapAssign($assignedExpr);
			$type = $scope->getType($assignedExpr);

			$conditionalExpressions = [];
			if ($assignedExpr instanceof Ternary) {
				$if = $assignedExpr->if;
				if ($if === null) {
					$if = $assignedExpr->cond;
				}
				$condScope = $this->processExprNode($stmt, $assignedExpr->cond, $scope, $storage, new NoopNodeCallback(), ExpressionContext::createDeep())->getScope();
				$truthySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($condScope, $assignedExpr->cond, TypeSpecifierContext::createTruthy());
				$falseySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($condScope, $assignedExpr->cond, TypeSpecifierContext::createFalsey());
				$truthyScope = $condScope->filterBySpecifiedTypes($truthySpecifiedTypes);
				$falsyScope = $condScope->filterBySpecifiedTypes($falseySpecifiedTypes);
				$truthyType = $truthyScope->getType($if);
				$falseyType = $falsyScope->getType($assignedExpr->else);

				if (
					$truthyType->isSuperTypeOf($falseyType)->no()
					&& $falseyType->isSuperTypeOf($truthyType)->no()
				) {
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType);
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($condScope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType);
				}
			}

			$scopeBeforeAssignEval = $scope;
			$scope = $result->getScope();
			$truthySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $assignedExpr, TypeSpecifierContext::createTruthy());
			$falseySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $assignedExpr, TypeSpecifierContext::createFalsey());

			$truthyType = TypeCombinator::removeFalsey($type);
			$falseyType = TypeCombinator::intersect($type, StaticTypeFactory::falsey());

			$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType);
			$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType);
			$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType);
			$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType);

			$this->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedExpr), $scopeBeforeAssignEval, $storage);
			$scope = $scope->assignVariable($var->name, $type, $scope->getNativeType($assignedExpr), TrinaryLogic::createYes());
			foreach ($conditionalExpressions as $exprString => $holders) {
				$scope = $scope->addConditionalExpressions($exprString, $holders);
			}
		} elseif ($var instanceof ArrayDimFetch) {
			$dimFetchStack = [];
			$originalVar = $var;
			$assignedPropertyExpr = $assignedExpr;
			while ($var instanceof ArrayDimFetch) {
				$varForSetOffsetValue = $var->var;
				if ($varForSetOffsetValue instanceof PropertyFetch || $varForSetOffsetValue instanceof StaticPropertyFetch) {
					$varForSetOffsetValue = new OriginalPropertyTypeExpr($varForSetOffsetValue);
				}

				if (
					$var === $originalVar
					&& $var->dim !== null
					&& $scope->hasExpressionType($var)->yes()
				) {
					$assignedPropertyExpr = new SetExistingOffsetValueTypeExpr(
						$varForSetOffsetValue,
						$var->dim,
						$assignedPropertyExpr,
					);
				} else {
					$assignedPropertyExpr = new SetOffsetValueTypeExpr(
						$varForSetOffsetValue,
						$var->dim,
						$assignedPropertyExpr,
					);
				}
				$dimFetchStack[] = $var;
				$var = $var->var;
			}

			// 1. eval root expr
			if ($enterExpressionAssign) {
				$scope = $scope->enterExpressionAssign($var);
			}
			$result = $this->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
			if ($enterExpressionAssign) {
				$scope = $scope->exitExpressionAssign($var);
			}

			// 2. eval dimensions
			$offsetTypes = [];
			$offsetNativeTypes = [];
			$dimFetchStack = array_reverse($dimFetchStack);
			$lastDimKey = array_key_last($dimFetchStack);
			foreach ($dimFetchStack as $key => $dimFetch) {
				$dimExpr = $dimFetch->dim;

				// Callback was already called for last dim at the beginning of the method.
				if ($key !== $lastDimKey) {
					$this->callNodeCallback($nodeCallback, $dimFetch, $enterExpressionAssign ? $scope->enterExpressionAssign($dimFetch) : $scope, $storage);
				}

				if ($dimExpr === null) {
					$offsetTypes[] = [null, $dimFetch];
					$offsetNativeTypes[] = [null, $dimFetch];

				} else {
					$offsetTypes[] = [$scope->getType($dimExpr), $dimFetch];
					$offsetNativeTypes[] = [$scope->getNativeType($dimExpr), $dimFetch];

					if ($enterExpressionAssign) {
						$scope->enterExpressionAssign($dimExpr);
					}
					$result = $this->processExprNode($stmt, $dimExpr, $scope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $result->hasYield();
					$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
					$scope = $result->getScope();

					if ($enterExpressionAssign) {
						$scope = $scope->exitExpressionAssign($dimExpr);
					}
				}
			}

			$valueToWrite = $scope->getType($assignedExpr);
			$nativeValueToWrite = $scope->getNativeType($assignedExpr);
			$scopeBeforeAssignEval = $scope;

			// 3. eval assigned expr
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			$varType = $scope->getType($var);
			$varNativeType = $scope->getNativeType($var);

			// 4. compose types
			$isImplicitArrayCreation = $this->isImplicitArrayCreation($dimFetchStack, $scope);
			if ($isImplicitArrayCreation->yes()) {
				$varType = new ConstantArrayType([], []);
				$varNativeType = new ConstantArrayType([], []);
			}
			$offsetValueType = $varType;
			$offsetNativeValueType = $varNativeType;

			[$valueToWrite, $additionalExpressions] = $this->produceArrayDimFetchAssignValueToWrite($dimFetchStack, $offsetTypes, $offsetValueType, $valueToWrite, $scope);

			if (!$offsetValueType->equals($offsetNativeValueType) || !$valueToWrite->equals($nativeValueToWrite)) {
				[$nativeValueToWrite, $additionalNativeExpressions] = $this->produceArrayDimFetchAssignValueToWrite($dimFetchStack, $offsetNativeTypes, $offsetNativeValueType, $nativeValueToWrite, $scope);
			} else {
				$rewritten = false;
				foreach ($offsetTypes as $i => [$offsetType]) {
					[$offsetNativeType] = $offsetNativeTypes[$i];

					if ($offsetType === null) {
						if ($offsetNativeType !== null) {
							throw new ShouldNotHappenException();
						}

						continue;
					} elseif ($offsetNativeType === null) {
						throw new ShouldNotHappenException();
					}
					if ($offsetType->equals($offsetNativeType)) {
						continue;
					}

					[$nativeValueToWrite] = $this->produceArrayDimFetchAssignValueToWrite($dimFetchStack, $offsetNativeTypes, $offsetNativeValueType, $nativeValueToWrite, $scope);
					$rewritten = true;
					break;
				}

				if (!$rewritten) {
					$nativeValueToWrite = $valueToWrite;
				}
			}

			if ($varType->isArray()->yes() || !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->yes()) {
				if ($var instanceof Variable && is_string($var->name)) {
					$this->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scopeBeforeAssignEval, $storage);
					$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite, TrinaryLogic::createYes());
				} else {
					if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
						$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
						if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
							$scope = $scope->assignInitializedProperty($scope->getType($var->var), $var->name->toString());
						}
					}
					$scope = $scope->assignExpression(
						$var,
						$valueToWrite,
						$nativeValueToWrite,
					);
				}
			} else {
				if ($var instanceof Variable) {
					$this->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scopeBeforeAssignEval, $storage);
				} elseif ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
					if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
						$scope = $scope->assignInitializedProperty($scope->getType($var->var), $var->name->toString());
					}
				}
			}

			foreach ($additionalExpressions as $k => $additionalExpression) {
				[$expr, $type] = $additionalExpression;
				$nativeType = $type;
				if (isset($additionalNativeExpressions[$k])) {
					[, $nativeType] = $additionalNativeExpressions[$k];
				}

				$scope = $scope->assignExpression($expr, $type, $nativeType);
			}

			if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
				$throwPoints = array_merge($throwPoints, $this->processExprNode(
					$stmt,
					new MethodCall($var, 'offsetSet'),
					$scope,
					$storage,
					new NoopNodeCallback(),
					$context,
				)->getThrowPoints());
			}
		} elseif ($var instanceof PropertyFetch) {
			$objectResult = $this->processExprNode($stmt, $var->var, $scope, $storage, $nodeCallback, $context);
			$hasYield = $objectResult->hasYield();
			$throwPoints = $objectResult->getThrowPoints();
			$impurePoints = $objectResult->getImpurePoints();
			$isAlwaysTerminating = $objectResult->isAlwaysTerminating();
			$scope = $objectResult->getScope();

			$propertyName = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $this->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $hasYield || $propertyNameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $propertyNameResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $propertyNameResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $propertyNameResult->isAlwaysTerminating();
				$scope = $propertyNameResult->getScope();
			}

			$scopeBeforeAssignEval = $scope;
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			if ($var->name instanceof Expr && $this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $var);
			}

			$propertyHolderType = $scope->getType($var->var);
			if ($propertyName !== null && $propertyHolderType->hasInstanceProperty($propertyName)->yes()) {
				$propertyReflection = $propertyHolderType->getInstanceProperty($propertyName, $scope);
				$assignedExprType = $scope->getType($assignedExpr);
				$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				if ($propertyReflection->canChangeTypeAfterAssignment()) {
					if ($propertyReflection->hasNativeType()) {
						$propertyNativeType = $propertyReflection->getNativeType();

						$assignedTypeIsCompatible = $propertyNativeType->isSuperTypeOf($assignedExprType)->yes();
						if (!$assignedTypeIsCompatible) {
							foreach (TypeUtils::flattenTypes($propertyNativeType) as $type) {
								if ($type->isSuperTypeOf($assignedExprType)->yes()) {
									$assignedTypeIsCompatible = true;
									break;
								}
							}
						}

						if ($assignedTypeIsCompatible) {
							$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
						} else {
							$scope = $scope->assignExpression(
								$var,
								TypeCombinator::intersect($assignedExprType->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
								TypeCombinator::intersect($scope->getNativeType($assignedExpr)->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
							);
						}
					} else {
						$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
					}
				}
				$declaringClass = $propertyReflection->getDeclaringClass();
				if ($declaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $declaringClass->getNativeProperty($propertyName);
					if (
						!$nativeProperty->getNativeType()->accepts($assignedExprType, true)->yes()
					) {
						$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(TypeError::class), $assignedExpr, false);
					}
					if ($this->phpVersion->supportsPropertyHooks()) {
						$throwPoints = array_merge($throwPoints, $this->getPropertyAssignThrowPointsFromSetHook($scope, $var, $nativeProperty));
					}
					if ($enterExpressionAssign) {
						$scope = $scope->assignInitializedProperty($propertyHolderType, $propertyName);
					}
				}
			} else {
				// fallback
				$assignedExprType = $scope->getType($assignedExpr);
				$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
				// simulate dynamic property assign by __set to get throw points
				if (!$propertyHolderType->hasMethod('__set')->no()) {
					$throwPoints = array_merge($throwPoints, $this->processExprNode(
						$stmt,
						new MethodCall($var->var, '__set'),
						$scope,
						$storage,
						new NoopNodeCallback(),
						$context,
					)->getThrowPoints());
				}
			}

		} elseif ($var instanceof Expr\StaticPropertyFetch) {
			if ($var->class instanceof Node\Name) {
				$propertyHolderType = $scope->resolveTypeByName($var->class);
			} else {
				$this->processExprNode($stmt, $var->class, $scope, $storage, $nodeCallback, $context);
				$propertyHolderType = $scope->getType($var->class);
			}

			$propertyName = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $this->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $propertyNameResult->hasYield();
				$throwPoints = $propertyNameResult->getThrowPoints();
				$impurePoints = $propertyNameResult->getImpurePoints();
				$isAlwaysTerminating = $propertyNameResult->isAlwaysTerminating();
				$scope = $propertyNameResult->getScope();
			}

			$scopeBeforeAssignEval = $scope;
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			if ($propertyName !== null) {
				$propertyReflection = $scope->getStaticPropertyReflection($propertyHolderType, $propertyName);
				$assignedExprType = $scope->getType($assignedExpr);
				$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				if ($propertyReflection !== null && $propertyReflection->canChangeTypeAfterAssignment()) {
					if ($propertyReflection->hasNativeType()) {
						$propertyNativeType = $propertyReflection->getNativeType();
						$assignedTypeIsCompatible = $propertyNativeType->isSuperTypeOf($assignedExprType)->yes();

						if (!$assignedTypeIsCompatible) {
							foreach (TypeUtils::flattenTypes($propertyNativeType) as $type) {
								if ($type->isSuperTypeOf($assignedExprType)->yes()) {
									$assignedTypeIsCompatible = true;
									break;
								}
							}
						}

						if ($assignedTypeIsCompatible) {
							$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
						} else {
							$scope = $scope->assignExpression(
								$var,
								TypeCombinator::intersect($assignedExprType->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
								TypeCombinator::intersect($scope->getNativeType($assignedExpr)->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
							);
						}
					} else {
						$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
					}
				}
			} else {
				// fallback
				$assignedExprType = $scope->getType($assignedExpr);
				$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
			}
		} elseif ($var instanceof List_) {
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
			foreach ($var->items as $i => $arrayItem) {
				if ($arrayItem === null) {
					continue;
				}

				$itemScope = $scope;
				if ($enterExpressionAssign) {
					$itemScope = $itemScope->enterExpressionAssign($arrayItem->value);
				}
				$itemScope = $this->lookForSetAllowedUndefinedExpressions($itemScope, $arrayItem->value);
				$this->callNodeCallback($nodeCallback, $arrayItem, $itemScope, $storage);
				if ($arrayItem->key !== null) {
					$keyResult = $this->processExprNode($stmt, $arrayItem->key, $itemScope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $keyResult->hasYield();
					$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
					$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
					$itemScope = $keyResult->getScope();
				}

				if ($arrayItem->key === null) {
					$dimExpr = new Node\Scalar\Int_($i);
				} else {
					$dimExpr = $arrayItem->key;
				}
				$result = $this->processAssignVar(
					$scope,
					$storage,
					$stmt,
					$arrayItem->value,
					new GetOffsetValueTypeExpr($assignedExpr, $dimExpr),
					$nodeCallback,
					$context,
					static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, $scope, false, false, [], []),
					$enterExpressionAssign,
				);
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			}
		} elseif ($var instanceof ExistingArrayDimFetch) {
			$dimFetchStack = [];
			$assignedPropertyExpr = $assignedExpr;
			while ($var instanceof ExistingArrayDimFetch) {
				$varForSetOffsetValue = $var->getVar();
				if ($varForSetOffsetValue instanceof PropertyFetch || $varForSetOffsetValue instanceof StaticPropertyFetch) {
					$varForSetOffsetValue = new OriginalPropertyTypeExpr($varForSetOffsetValue);
				}
				$assignedPropertyExpr = new SetExistingOffsetValueTypeExpr(
					$varForSetOffsetValue,
					$var->getDim(),
					$assignedPropertyExpr,
				);
				$dimFetchStack[] = $var;
				$var = $var->getVar();
			}

			$offsetTypes = [];
			$offsetNativeTypes = [];
			foreach (array_reverse($dimFetchStack) as $dimFetch) {
				$dimExpr = $dimFetch->getDim();
				$offsetTypes[] = [$scope->getType($dimExpr), $dimFetch];
				$offsetNativeTypes[] = [$scope->getNativeType($dimExpr), $dimFetch];
			}

			$valueToWrite = $scope->getType($assignedExpr);
			$nativeValueToWrite = $scope->getNativeType($assignedExpr);
			$varType = $scope->getType($var);
			$varNativeType = $scope->getNativeType($var);

			$offsetValueType = $varType;
			$offsetNativeValueType = $varNativeType;
			$offsetValueTypeStack = [$offsetValueType];
			$offsetValueNativeTypeStack = [$offsetNativeValueType];
			foreach (array_slice($offsetTypes, 0, -1) as [$offsetType]) {
				$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
				$offsetValueTypeStack[] = $offsetValueType;
			}
			foreach (array_slice($offsetNativeTypes, 0, -1) as [$offsetNativeType]) {
				$offsetNativeValueType = $offsetNativeValueType->getOffsetValueType($offsetNativeType);
				$offsetValueNativeTypeStack[] = $offsetNativeValueType;
			}

			foreach (array_reverse($offsetTypes) as [$offsetType]) {
				/** @var Type $offsetValueType */
				$offsetValueType = array_pop($offsetValueTypeStack);
				$valueToWrite = $offsetValueType->setExistingOffsetValueType($offsetType, $valueToWrite);
			}
			foreach (array_reverse($offsetNativeTypes) as [$offsetNativeType]) {
				/** @var Type $offsetNativeValueType */
				$offsetNativeValueType = array_pop($offsetValueNativeTypeStack);
				$nativeValueToWrite = $offsetNativeValueType->setExistingOffsetValueType($offsetNativeType, $nativeValueToWrite);
			}

			if ($var instanceof Variable && is_string($var->name)) {
				$this->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scope, $storage);
				$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite, TrinaryLogic::createYes());
			} else {
				if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$this->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scope, $storage);
				}
				$scope = $scope->assignExpression(
					$var,
					$valueToWrite,
					$nativeValueToWrite,
				);
			}
		} else {
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}

		// stored where processAssignVar is called
		return new ExpressionResult($scope, $originalScope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processVirtualAssign(MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, Expr $var, Expr $assignedExpr, callable $nodeCallback): ExpressionResult
	{
		return $this->processAssignVar(
			$scope,
			$storage,
			$stmt,
			$var,
			$assignedExpr,
			new VirtualAssignNodeCallback($nodeCallback),
			ExpressionContext::createDeep(),
			static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, $scope, false, false, [], []),
			false,
		);
	}

	/**
	 * @param list<ArrayDimFetch> $dimFetchStack
	 */
	private function isImplicitArrayCreation(array $dimFetchStack, Scope $scope): TrinaryLogic
	{
		if (count($dimFetchStack) === 0) {
			return TrinaryLogic::createNo();
		}

		$varNode = $dimFetchStack[0]->var;
		if (!$varNode instanceof Variable) {
			return TrinaryLogic::createNo();
		}

		if (!is_string($varNode->name)) {
			return TrinaryLogic::createNo();
		}

		return $scope->hasVariableType($varNode->name)->negate();
	}

	/**
	 * @param list<ArrayDimFetch> $dimFetchStack
	 * @param list<array{Type|null, ArrayDimFetch}> $offsetTypes
	 *
	 * @return array{Type, list<array{Expr, Type}>}
	 */
	private function produceArrayDimFetchAssignValueToWrite(array $dimFetchStack, array $offsetTypes, Type $offsetValueType, Type $valueToWrite, Scope $scope): array
	{
		$originalValueToWrite = $valueToWrite;

		$offsetValueTypeStack = [$offsetValueType];
		foreach (array_slice($offsetTypes, 0, -1) as [$offsetType, $dimFetch]) {
			if ($offsetType === null) {
				$offsetValueType = new ConstantArrayType([], []);

			} else {
				$has = $offsetValueType->hasOffsetValueType($offsetType);
				if ($has->yes()) {
					$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
				} elseif ($has->maybe()) {
					if (!$scope->hasExpressionType($dimFetch)->yes()) {
						$offsetValueType = TypeCombinator::union($offsetValueType->getOffsetValueType($offsetType), new ConstantArrayType([], []));
					} else {
						$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
					}
				} else {
					$offsetValueType = new ConstantArrayType([], []);
				}
			}

			$offsetValueTypeStack[] = $offsetValueType;
		}

		foreach (array_reverse($offsetTypes) as $i => [$offsetType]) {
			/** @var Type $offsetValueType */
			$offsetValueType = array_pop($offsetValueTypeStack);
			if (
				!$offsetValueType instanceof MixedType
				&& !$offsetValueType->isConstantArray()->yes()
			) {
				$types = [
					new ArrayType(new MixedType(), new MixedType()),
					new ObjectType(ArrayAccess::class),
					new NullType(),
				];
				if ($offsetType !== null && $offsetType->isInteger()->yes()) {
					$types[] = new StringType();
				}
				$offsetValueType = TypeCombinator::intersect($offsetValueType, TypeCombinator::union(...$types));
			}

			$arrayDimFetch = $dimFetchStack[$i] ?? null;
			if (
				$offsetType !== null
				&& $arrayDimFetch !== null
				&& $scope->hasExpressionType($arrayDimFetch)->yes()
			) {
				$hasOffsetType = null;
				if ($offsetType instanceof ConstantStringType || $offsetType instanceof ConstantIntegerType) {
					$hasOffsetType = new HasOffsetValueType($offsetType, $valueToWrite);
				}
				$valueToWrite = $offsetValueType->setExistingOffsetValueType($offsetType, $valueToWrite);

				if ($valueToWrite->isArray()->yes()) {
					if ($hasOffsetType !== null) {
						$valueToWrite = TypeCombinator::intersect(
							$valueToWrite,
							$hasOffsetType,
						);
					} else {
						$valueToWrite = TypeCombinator::intersect(
							$valueToWrite,
							new NonEmptyArrayType(),
						);
					}
				}

			} else {
				$valueToWrite = $offsetValueType->setOffsetValueType($offsetType, $valueToWrite, $i === 0);
			}

			if ($arrayDimFetch === null || !$offsetValueType->isList()->yes()) {
				continue;
			}

			if (!$arrayDimFetch->dim instanceof BinaryOp\Plus) {
				continue;
			}

			if ( // keep list for $list[$index + 1] assignments
				$arrayDimFetch->dim->right instanceof Variable
				&& $arrayDimFetch->dim->left instanceof Node\Scalar\Int_
				&& $arrayDimFetch->dim->left->value === 1
				&& $scope->hasExpressionType(new ArrayDimFetch($arrayDimFetch->var, $arrayDimFetch->dim->right))->yes()
			) {
				$valueToWrite = TypeCombinator::intersect($valueToWrite, new AccessoryArrayListType());
			} elseif ( // keep list for $list[1 + $index] assignments
				$arrayDimFetch->dim->left instanceof Variable
				&& $arrayDimFetch->dim->right instanceof Node\Scalar\Int_
				&& $arrayDimFetch->dim->right->value === 1
				&& $scope->hasExpressionType(new ArrayDimFetch($arrayDimFetch->var, $arrayDimFetch->dim->left))->yes()
			) {
				$valueToWrite = TypeCombinator::intersect($valueToWrite, new AccessoryArrayListType());
			}
		}

		$additionalExpressions = [];
		$offsetValueType = $valueToWrite;
		$lastDimKey = array_key_last($dimFetchStack);
		foreach ($dimFetchStack as $key => $dimFetch) {
			if ($dimFetch->dim === null) {
				continue;
			}

			if ($key === $lastDimKey) {
				$offsetValueType = $originalValueToWrite;
			} else {
				$offsetType = $scope->getType($dimFetch->dim);
				$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
			}

			$additionalExpressions[] = [$dimFetch, $offsetValueType];
		}

		return [$valueToWrite, $additionalExpressions];
	}

	private function unwrapAssign(Expr $expr): Expr
	{
		if ($expr instanceof Assign) {
			return $this->unwrapAssign($expr->expr);
		}

		return $expr;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processSureTypesForConditionalExpressionsAfterAssign(Scope $scope, string $variableName, array $conditionalExpressions, SpecifiedTypes $specifiedTypes, Type $variableType): array
	{
		foreach ($specifiedTypes->getSureTypes() as $exprString => [$expr, $exprType]) {
			if (!$expr instanceof Variable) {
				continue;
			}
			if (!is_string($expr->name)) {
				continue;
			}

			if ($expr->name === $variableName) {
				continue;
			}

			if (!isset($conditionalExpressions[$exprString])) {
				$conditionalExpressions[$exprString] = [];
			}

			$holder = new ConditionalExpressionHolder([
				'$' . $variableName => ExpressionTypeHolder::createYes(new Variable($variableName), $variableType),
			], ExpressionTypeHolder::createYes(
				$expr,
				TypeCombinator::intersect($scope->getType($expr), $exprType),
			));
			$conditionalExpressions[$exprString][$holder->getKey()] = $holder;
		}

		return $conditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processSureNotTypesForConditionalExpressionsAfterAssign(Scope $scope, string $variableName, array $conditionalExpressions, SpecifiedTypes $specifiedTypes, Type $variableType): array
	{
		foreach ($specifiedTypes->getSureNotTypes() as $exprString => [$expr, $exprType]) {
			if (!$expr instanceof Variable) {
				continue;
			}
			if (!is_string($expr->name)) {
				continue;
			}

			if ($expr->name === $variableName) {
				continue;
			}

			if (!isset($conditionalExpressions[$exprString])) {
				$conditionalExpressions[$exprString] = [];
			}

			$holder = new ConditionalExpressionHolder([
				'$' . $variableName => ExpressionTypeHolder::createYes(new Variable($variableName), $variableType),
			], ExpressionTypeHolder::createYes(
				$expr,
				TypeCombinator::remove($scope->getType($expr), $exprType),
			));
			$conditionalExpressions[$exprString][$holder->getKey()] = $holder;
		}

		return $conditionalExpressions;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processStmtVarAnnotation(MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, ?Expr $defaultExpr, callable $nodeCallback): MutatingScope
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
	private function processVarAnnotation(MutatingScope $scope, array $variableNames, Node\Stmt $node, bool &$changed = false): MutatingScope
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
			$keyVarName = null;
			if ($stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)) {
				$keyVarName = $stmt->keyVar->name;
			}
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
				new GetIterableValueTypeExpr($stmt->expr),
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
					new GetIterableKeyTypeExpr($stmt->expr),
					$nodeCallback,
				)->getScope();
				$vars = array_merge($vars, $this->getAssignedVariables($stmt->keyVar));
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
				$holder = new ConditionalExpressionHolder([
					'$' . $stmt->keyVar->name => ExpressionTypeHolder::createYes(new Variable($stmt->keyVar->name), $keyType),
				], new ExpressionTypeHolder($stmt->valueVar, $valueType, TrinaryLogic::createYes()));
				$valueConditionalHolders[$holder->getKey()] = $holder;
				$arrayDimFetchHolder = new ConditionalExpressionHolder([
					'$' . $stmt->keyVar->name => ExpressionTypeHolder::createYes(new Variable($stmt->keyVar->name), $keyType),
				], new ExpressionTypeHolder(new ArrayDimFetch($stmt->expr, $stmt->keyVar), $valueType, TrinaryLogic::createYes()));
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

	private function processCalledMethod(MethodReflection $methodReflection): ?MutatingScope
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
	 * @return array{TemplateTypeMap, array<string, Type>, array<string, bool>, array<string, Type>, ?Type, ?Type, ?string, bool, bool, bool, bool|null, bool, bool, string|null, Assertions, ?Type, array<string, Type>, array<(string|int), VarTag>, bool}
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
			$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
				$docComment,
				$file,
				$scope->getClassReflection(),
				$trait,
				$node->name->name,
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
			if ($acceptsNamedArguments && $scope->isInClass()) {
				$acceptsNamedArguments = $scope->getClassReflection()->acceptsNamedArguments();
			}
			$isReadOnly = $isReadOnly || $resolvedPhpDoc->isReadOnly();
			$asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
			$selfOutType = $resolvedPhpDoc->getSelfOutTag() !== null ? $resolvedPhpDoc->getSelfOutTag()->getType() : null;
			$varTags = $resolvedPhpDoc->getVarTags();
		}

		return [$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $docComment, $asserts, $selfOutType, $phpDocParameterOutTypes, $varTags, $isAllowedPrivateMutation];
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

	/**
	 * @param array<Expr> $conditions
	 */
	private function getFilteringExprForMatchArm(Expr\Match_ $expr, array $conditions): BinaryOp\Identical|FuncCall
	{
		if (count($conditions) === 1) {
			return new BinaryOp\Identical($expr->cond, $conditions[0]);
		}

		$items = [];
		foreach ($conditions as $filteringExpr) {
			$items[] = new Node\ArrayItem($filteringExpr);
		}

		return new FuncCall(
			new Name\FullyQualified('in_array'),
			[
				new Arg($expr->cond),
				new Arg(new Array_($items)),
				new Arg(new ConstFetch(new Name\FullyQualified('true'))),
			],
		);
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
				&& $lastCondExpr->right->name->toLowerString() === 'count'
				&& count($lastCondExpr->right->getArgs()) > 0
				&& $lastCondExpr->right->getArgs()[0]->value instanceof Variable
				&& is_string($stmt->init[0]->var->name)
				&& $stmt->init[0]->var->name === $stmt->loop[0]->var->name
				&& $stmt->init[0]->var->name === $lastCondExpr->left->name
			) {
				$arrayArg = $lastCondExpr->right->getArgs()[0]->value;
				$bodyScope = $bodyScope->assignExpression(
					new ArrayDimFetch($lastCondExpr->right->getArgs()[0]->value, $lastCondExpr->left),
					$bodyScope->getType($arrayArg)->getIterableValueType(),
					$bodyScope->getNativeType($arrayArg)->getIterableValueType(),
				);
			}

			// count($items) > $i
			if (
				$lastCondExpr instanceof BinaryOp\Greater
				&& $lastCondExpr->right instanceof Variable
				&& $lastCondExpr->left instanceof FuncCall
				&& $lastCondExpr->left->name instanceof Name
				&& $lastCondExpr->left->name->toLowerString() === 'count'
				&& count($lastCondExpr->left->getArgs()) > 0
				&& $lastCondExpr->left->getArgs()[0]->value instanceof Variable
				&& is_string($stmt->init[0]->var->name)
				&& $stmt->init[0]->var->name === $stmt->loop[0]->var->name
				&& $stmt->init[0]->var->name === $lastCondExpr->right->name
			) {
				$arrayArg = $lastCondExpr->left->getArgs()[0]->value;
				$bodyScope = $bodyScope->assignExpression(
					new ArrayDimFetch($lastCondExpr->left->getArgs()[0]->value, $lastCondExpr->right),
					$bodyScope->getType($arrayArg)->getIterableValueType(),
					$bodyScope->getNativeType($arrayArg)->getIterableValueType(),
				);
			}
		}

		return $bodyScope;
	}

}
