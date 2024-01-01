<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use ArrayAccess;
use Closure;
use DivisionByZeroError;
use PhpParser\Comment\Doc;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\AttributeGroup;
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
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\Node\Stmt\While_;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
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
use PHPStan\Node\DoWhileLoopConditionNode;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\FinallyExitPointsNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InClassNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Node\InForeachNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\MatchExpressionArm;
use PHPStan\Node\MatchExpressionArmBody;
use PHPStan\Node\MatchExpressionArmCondition;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Parser\ArrowFunctionArgVisitor;
use PHPStan\Parser\ClosureArgVisitor;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
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
use function base64_decode;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;
use const PHP_VERSION_ID;

class NodeScopeResolver
{

	private const LOOP_SCOPE_ITERATIONS = 3;
	private const GENERALIZE_AFTER_ITERATION = 1;

	/** @var bool[] filePath(string) => bool(true) */
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
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		private readonly InitializerExprTypeResolver $initializerExprTypeResolver,
		private readonly Reflector $reflector,
		private readonly ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider,
		private readonly Parser $parser,
		private readonly FileTypeMapper $fileTypeMapper,
		private readonly StubPhpDocProvider $stubPhpDocProvider,
		private readonly PhpVersion $phpVersion,
		private readonly SignatureMapProvider $signatureMapProvider,
		private readonly PhpDocInheritanceResolver $phpDocInheritanceResolver,
		private readonly FileHelper $fileHelper,
		private readonly TypeSpecifier $typeSpecifier,
		private readonly DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		private readonly ReadWritePropertiesExtensionProvider $readWritePropertiesExtensionProvider,
		private readonly ScopeFactory $scopeFactory,
		private readonly bool $polluteScopeWithLoopInitialAssignments,
		private readonly bool $polluteScopeWithAlwaysIterableForeach,
		private readonly array $earlyTerminatingMethodCalls,
		private readonly array $earlyTerminatingFunctionCalls,
		private readonly array $universalObjectCratesClasses,
		private readonly bool $implicitThrows,
		private readonly bool $treatPhpDocTypesAsCertain,
		private readonly bool $detectDeadTypeInMultiCatch,
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
		foreach ($nodes as $i => $node) {
			if (!$node instanceof Node\Stmt) {
				continue;
			}

			$statementResult = $this->processStmtNode($node, $scope, $nodeCallback, StatementContext::createTopLevel());
			$scope = $statementResult->getScope();
			if (!$statementResult->isAlwaysTerminating()) {
				continue;
			}

			$nextStmt = $this->getFirstUnreachableNode(array_slice($nodes, $i + 1), true);
			if (!$nextStmt instanceof Node\Stmt) {
				continue;
			}

			$nodeCallback(new UnreachableStatementNode($nextStmt), $scope);
			break;
		}
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
		?StatementContext $context = null,
	): StatementResult
	{
		if ($context === null) {
			$context = StatementContext::createTopLevel();
		}
		$exitPoints = [];
		$throwPoints = [];
		$alreadyTerminated = false;
		$hasYield = false;
		$stmtCount = count($stmts);
		$shouldCheckLastStatement = $parentNode instanceof Node\Stmt\Function_
			|| $parentNode instanceof Node\Stmt\ClassMethod
			|| $parentNode instanceof Expr\Closure;
		foreach ($stmts as $i => $stmt) {
			$isLast = $i === $stmtCount - 1;
			$statementResult = $this->processStmtNode(
				$stmt,
				$scope,
				$nodeCallback,
				$context,
			);
			$scope = $statementResult->getScope();
			$hasYield = $hasYield || $statementResult->hasYield();

			if ($shouldCheckLastStatement && $isLast) {
				/** @var Node\Stmt\Function_|Node\Stmt\ClassMethod|Expr\Closure $parentNode */
				$parentNode = $parentNode;
				$nodeCallback(new ExecutionEndNode(
					$stmt,
					new StatementResult(
						$scope,
						$hasYield,
						$statementResult->isAlwaysTerminating(),
						$statementResult->getExitPoints(),
						$statementResult->getThrowPoints(),
					),
					$parentNode->returnType !== null,
				), $scope);
			}

			$exitPoints = array_merge($exitPoints, $statementResult->getExitPoints());
			$throwPoints = array_merge($throwPoints, $statementResult->getThrowPoints());

			if (!$statementResult->isAlwaysTerminating()) {
				continue;
			}

			$alreadyTerminated = true;
			$nextStmt = $this->getFirstUnreachableNode(array_slice($stmts, $i + 1), $parentNode instanceof Node\Stmt\Namespace_);
			if ($nextStmt !== null) {
				$nodeCallback(new UnreachableStatementNode($nextStmt), $scope);
			}
			break;
		}

		$statementResult = new StatementResult($scope, $hasYield, $alreadyTerminated, $exitPoints, $throwPoints);
		if ($stmtCount === 0 && $shouldCheckLastStatement) {
			/** @var Node\Stmt\Function_|Node\Stmt\ClassMethod|Expr\Closure $parentNode */
			$parentNode = $parentNode;
			$nodeCallback(new ExecutionEndNode(
				$parentNode,
				$statementResult,
				$parentNode->returnType !== null,
			), $scope);
		}

		return $statementResult;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processStmtNode(
		Node\Stmt $stmt,
		MutatingScope $scope,
		callable $nodeCallback,
		StatementContext $context,
	): StatementResult
	{
		if (
			!$stmt instanceof Static_
			&& !$stmt instanceof Foreach_
			&& !$stmt instanceof Node\Stmt\Global_
			&& !$stmt instanceof Node\Stmt\Property
			&& !$stmt instanceof Node\Stmt\ClassConst
			&& !$stmt instanceof Node\Stmt\Const_
		) {
			$scope = $this->processStmtVarAnnotation($scope, $stmt, null, $nodeCallback);
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
					return new StatementResult($scope, false, false, [], []);
				}
				if ($methodReflection instanceof PhpMethodReflection) {
					$declaringTrait = $methodReflection->getDeclaringTrait();
					if ($declaringTrait === null || $declaringTrait->getName() !== $scope->getTraitReflection()->getName()) {
						return new StatementResult($scope, false, false, [], []);
					}
				}
			}
		}

		$stmtScope = $scope;
		if ($stmt instanceof Node\Stmt\Expression && $stmt->expr instanceof Expr\Throw_) {
			$stmtScope = $this->processStmtVarAnnotation($scope, $stmt, $stmt->expr->expr, $nodeCallback);
		}
		if ($stmt instanceof Return_) {
			$stmtScope = $this->processStmtVarAnnotation($scope, $stmt, $stmt->expr, $nodeCallback);
		}

		$nodeCallback($stmt, $stmtScope);

		$overridingThrowPoints = $this->getOverridingThrowPoints($stmt, $scope);

		if ($stmt instanceof Node\Stmt\Declare_) {
			$hasYield = false;
			$throwPoints = [];
			$alwaysTerminating = false;
			$exitPoints = [];
			foreach ($stmt->declares as $declare) {
				$nodeCallback($declare, $scope);
				$nodeCallback($declare->value, $scope);
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
				$result = $this->processStmtNodes($stmt, $stmt->stmts, $scope, $nodeCallback, $context);
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$alwaysTerminating = $result->isAlwaysTerminating();
				$exitPoints = $result->getExitPoints();
			}

			return new StatementResult($scope, $hasYield, $alwaysTerminating, $exitPoints, $throwPoints);
		} elseif ($stmt instanceof Node\Stmt\Function_) {
			$hasYield = false;
			$throwPoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $nodeCallback);
			[$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts,, $phpDocParameterOutTypes] = $this->getPhpDocs($scope, $stmt);

			foreach ($stmt->params as $param) {
				$this->processParamNode($stmt, $param, $scope, $nodeCallback);
			}

			if ($stmt->returnType !== null) {
				$nodeCallback($stmt->returnType, $scope);
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
				$isFinal,
				$isPure,
				$acceptsNamedArguments,
				$asserts,
				$phpDocComment,
				$phpDocParameterOutTypes,
			);
			$functionReflection = $functionScope->getFunction();
			if (!$functionReflection instanceof PhpFunctionFromParserNodeReflection) {
				throw new ShouldNotHappenException();
			}

			$nodeCallback(new InFunctionNode($functionReflection, $stmt), $functionScope);

			$gatheredReturnStatements = [];
			$gatheredYieldStatements = [];
			$executionEnds = [];
			$statementResult = $this->processStmtNodes($stmt, $stmt->stmts, $functionScope, static function (Node $node, Scope $scope) use ($nodeCallback, $functionScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds): void {
				$nodeCallback($node, $scope);
				if ($scope->getFunction() !== $functionScope->getFunction()) {
					return;
				}
				if ($scope->isInAnonymousFunction()) {
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
			}, StatementContext::createTopLevel());

			$nodeCallback(new FunctionReturnStatementsNode(
				$stmt,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$statementResult,
				$executionEnds,
				$functionReflection,
			), $functionScope);
		} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
			$hasYield = false;
			$throwPoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $nodeCallback);
			[$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes] = $this->getPhpDocs($scope, $stmt);

			foreach ($stmt->params as $param) {
				$this->processParamNode($stmt, $param, $scope, $nodeCallback);
			}

			if ($stmt->returnType !== null) {
				$nodeCallback($stmt->returnType, $scope);
			}

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
			);

			if (!$scope->isInClass()) {
				throw new ShouldNotHappenException();
			}

			$isFromTrait = $stmt->getAttribute('originalTraitMethodName') === '__construct';
			if ($stmt->name->toLowerString() === '__construct' || $isFromTrait) {
				foreach ($stmt->params as $param) {
					if ($param->flags === 0) {
						continue;
					}

					if (!$param->var instanceof Variable || !is_string($param->var->name)) {
						throw new ShouldNotHappenException();
					}
					$phpDoc = null;
					if ($param->getDocComment() !== null) {
						$phpDoc = $param->getDocComment()->getText();
					}
					$nodeCallback(new ClassPropertyNode(
						$param->var->name,
						$param->flags,
						$param->type,
						null,
						$phpDoc,
						$phpDocParameterTypes[$param->var->name] ?? null,
						true,
						$isFromTrait,
						$param,
						false,
						$scope->isInTrait(),
						$scope->getClassReflection()->isReadOnly(),
						false,
						$scope->getClassReflection(),
					), $methodScope);
					$methodScope = $methodScope->assignExpression(new PropertyInitializationExpr($param->var->name), new MixedType(), new MixedType());
				}
			}

			if ($stmt->getAttribute('virtual', false) === false) {
				$methodReflection = $methodScope->getFunction();
				if (!$methodReflection instanceof PhpMethodFromParserNodeReflection) {
					throw new ShouldNotHappenException();
				}
				$nodeCallback(new InClassMethodNode($scope->getClassReflection(), $methodReflection, $stmt), $methodScope);
			}

			if ($stmt->stmts !== null) {
				$gatheredReturnStatements = [];
				$gatheredYieldStatements = [];
				$executionEnds = [];
				$statementResult = $this->processStmtNodes($stmt, $stmt->stmts, $methodScope, static function (Node $node, Scope $scope) use ($nodeCallback, $methodScope, &$gatheredReturnStatements, &$gatheredYieldStatements, &$executionEnds): void {
					$nodeCallback($node, $scope);
					if ($scope->getFunction() !== $methodScope->getFunction()) {
						return;
					}
					if ($scope->isInAnonymousFunction()) {
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
				}, StatementContext::createTopLevel());

				$classReflection = $scope->getClassReflection();

				$methodReflection = $methodScope->getFunction();
				if (!$methodReflection instanceof MethodReflection) {
					throw new ShouldNotHappenException();
				}

				$nodeCallback(new MethodReturnStatementsNode(
					$stmt,
					$gatheredReturnStatements,
					$gatheredYieldStatements,
					$statementResult,
					$executionEnds,
					$classReflection,
					$methodReflection,
				), $methodScope);
			}
		} elseif ($stmt instanceof Echo_) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->exprs as $echoExpr) {
				$result = $this->processExprNode($stmt, $echoExpr, $scope, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
			}

			$throwPoints = $overridingThrowPoints ?? $throwPoints;
		} elseif ($stmt instanceof Return_) {
			if ($stmt->expr !== null) {
				$result = $this->processExprNode($stmt, $stmt->expr, $scope, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = $result->getThrowPoints();
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
			} else {
				$hasYield = false;
				$throwPoints = [];
			}

			return new StatementResult($scope, $hasYield, true, [
				new StatementExitPoint($stmt, $scope),
			], $overridingThrowPoints ?? $throwPoints);
		} elseif ($stmt instanceof Continue_ || $stmt instanceof Break_) {
			if ($stmt->num !== null) {
				$result = $this->processExprNode($stmt, $stmt->num, $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
			} else {
				$hasYield = false;
				$throwPoints = [];
			}

			return new StatementResult($scope, $hasYield, true, [
				new StatementExitPoint($stmt, $scope),
			], $overridingThrowPoints ?? $throwPoints);
		} elseif ($stmt instanceof Node\Stmt\Expression) {
			$earlyTerminationExpr = $this->findEarlyTerminatingExpr($stmt->expr, $scope);
			$result = $this->processExprNode($stmt, $stmt->expr, $scope, $nodeCallback, ExpressionContext::createTopLevel());
			$scope = $result->getScope();
			$scope = $scope->filterBySpecifiedTypes($this->typeSpecifier->specifyTypesInCondition(
				$scope,
				$stmt->expr,
				TypeSpecifierContext::createNull(),
			));
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			if ($earlyTerminationExpr !== null) {
				return new StatementResult($scope, $hasYield, true, [
					new StatementExitPoint($stmt, $scope),
				], $overridingThrowPoints ?? $throwPoints);
			}
			return new StatementResult($scope, $hasYield, false, [], $overridingThrowPoints ?? $throwPoints);
		} elseif ($stmt instanceof Node\Stmt\Namespace_) {
			if ($stmt->name !== null) {
				$scope = $scope->enterNamespace($stmt->name->toString());
			}

			$scope = $this->processStmtNodes($stmt, $stmt->stmts, $scope, $nodeCallback, $context)->getScope();
			$hasYield = false;
			$throwPoints = [];
		} elseif ($stmt instanceof Node\Stmt\Trait_) {
			return new StatementResult($scope, false, false, [], []);
		} elseif ($stmt instanceof Node\Stmt\ClassLike) {
			$hasYield = false;
			$throwPoints = [];
			if (isset($stmt->namespacedName)) {
				$classReflection = $this->getCurrentClassReflection($stmt, $stmt->namespacedName->toString(), $scope);
				$classScope = $scope->enterClass($classReflection);
				$nodeCallback(new InClassNode($stmt, $classReflection), $classScope);
			} elseif ($stmt instanceof Class_) {
				if ($stmt->name === null) {
					throw new ShouldNotHappenException();
				}
				if ($stmt->getAttribute('anonymousClass', false) === false) {
					$classReflection = $this->reflectionProvider->getClass($stmt->name->toString());
				} else {
					$classReflection = $this->reflectionProvider->getAnonymousClassReflection($stmt, $scope);
				}
				$classScope = $scope->enterClass($classReflection);
				$nodeCallback(new InClassNode($stmt, $classReflection), $classScope);
			} else {
				throw new ShouldNotHappenException();
			}

			$classStatementsGatherer = new ClassStatementsGatherer($classReflection, $nodeCallback);
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $classScope, $classStatementsGatherer);

			$this->processStmtNodes($stmt, $stmt->stmts, $classScope, $classStatementsGatherer, $context);
			$nodeCallback(new ClassPropertiesNode($stmt, $this->readWritePropertiesExtensionProvider, $classStatementsGatherer->getProperties(), $classStatementsGatherer->getPropertyUsages(), $classStatementsGatherer->getMethodCalls(), $classStatementsGatherer->getReturnStatementsNodes(), $classReflection), $classScope);
			$nodeCallback(new ClassMethodsNode($stmt, $classStatementsGatherer->getMethods(), $classStatementsGatherer->getMethodCalls(), $classReflection), $classScope);
			$nodeCallback(new ClassConstantsNode($stmt, $classStatementsGatherer->getConstants(), $classStatementsGatherer->getConstantFetches(), $classReflection), $classScope);
			$classReflection->evictPrivateSymbols();
			$this->calledMethodResults = [];
		} elseif ($stmt instanceof Node\Stmt\Property) {
			$hasYield = false;
			$throwPoints = [];
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $nodeCallback);

			foreach ($stmt->props as $prop) {
				if ($prop->default !== null) {
					$this->processExprNode($stmt, $prop->default, $scope, $nodeCallback, ExpressionContext::createDeep());
				}
				[,,,,,,,,,,$isReadOnly, $docComment, ,,,$varTags, $isAllowedPrivateMutation] = $this->getPhpDocs($scope, $stmt);
				if (!$scope->isInClass()) {
					throw new ShouldNotHappenException();
				}
				$propertyName = $prop->name->toString();
				$phpDocType = null;
				if (isset($varTags[0]) && count($varTags) === 1) {
					$phpDocType = $varTags[0]->getType();
				} elseif (isset($varTags[$propertyName])) {
					$phpDocType = $varTags[$propertyName]->getType();
				}
				$nodeCallback(
					new ClassPropertyNode(
						$propertyName,
						$stmt->flags,
						$stmt->type,
						$prop->default,
						$docComment,
						$phpDocType,
						false,
						false,
						$prop,
						$isReadOnly,
						$scope->isInTrait(),
						$scope->getClassReflection()->isReadOnly(),
						$isAllowedPrivateMutation,
						$scope->getClassReflection(),
					),
					$scope,
				);
			}

			if ($stmt->type !== null) {
				$nodeCallback($stmt->type, $scope);
			}
		} elseif ($stmt instanceof If_) {
			$conditionType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($stmt->cond) : $scope->getNativeType($stmt->cond))->toBoolean();
			$ifAlwaysTrue = $conditionType->isTrue()->yes();
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, $nodeCallback, ExpressionContext::createDeep());
			$exitPoints = [];
			$throwPoints = $overridingThrowPoints ?? $condResult->getThrowPoints();
			$finalScope = null;
			$alwaysTerminating = true;
			$hasYield = $condResult->hasYield();

			$branchScopeStatementResult = $this->processStmtNodes($stmt, $stmt->stmts, $condResult->getTruthyScope(), $nodeCallback, $context);

			if (!$conditionType instanceof ConstantBooleanType || $conditionType->getValue()) {
				$exitPoints = $branchScopeStatementResult->getExitPoints();
				$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
				$branchScope = $branchScopeStatementResult->getScope();
				$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? null : $branchScope;
				$alwaysTerminating = $branchScopeStatementResult->isAlwaysTerminating();
				$hasYield = $branchScopeStatementResult->hasYield() || $hasYield;
			}

			$scope = $condResult->getFalseyScope();
			$lastElseIfConditionIsTrue = false;

			$condScope = $scope;
			foreach ($stmt->elseifs as $elseif) {
				$nodeCallback($elseif, $scope);
				$elseIfConditionType = ($this->treatPhpDocTypesAsCertain ? $condScope->getType($elseif->cond) : $scope->getNativeType($elseif->cond))->toBoolean();
				$condResult = $this->processExprNode($stmt, $elseif->cond, $condScope, $nodeCallback, ExpressionContext::createDeep());
				$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
				$condScope = $condResult->getScope();
				$branchScopeStatementResult = $this->processStmtNodes($elseif, $elseif->stmts, $condResult->getTruthyScope(), $nodeCallback, $context);

				if (
					!$ifAlwaysTrue
					&& (
						!$lastElseIfConditionIsTrue
						&& (
							!$elseIfConditionType instanceof ConstantBooleanType
							|| $elseIfConditionType->getValue()
						)
					)
				) {
					$exitPoints = array_merge($exitPoints, $branchScopeStatementResult->getExitPoints());
					$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
					$branchScope = $branchScopeStatementResult->getScope();
					$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope);
					$alwaysTerminating = $alwaysTerminating && $branchScopeStatementResult->isAlwaysTerminating();
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
				$nodeCallback($stmt->else, $scope);
				$branchScopeStatementResult = $this->processStmtNodes($stmt->else, $stmt->else->stmts, $scope, $nodeCallback, $context);

				if (!$ifAlwaysTrue && !$lastElseIfConditionIsTrue) {
					$exitPoints = array_merge($exitPoints, $branchScopeStatementResult->getExitPoints());
					$throwPoints = array_merge($throwPoints, $branchScopeStatementResult->getThrowPoints());
					$branchScope = $branchScopeStatementResult->getScope();
					$finalScope = $branchScopeStatementResult->isAlwaysTerminating() ? $finalScope : $branchScope->mergeWith($finalScope);
					$alwaysTerminating = $alwaysTerminating && $branchScopeStatementResult->isAlwaysTerminating();
					$hasYield = $hasYield || $branchScopeStatementResult->hasYield();
				}
			}

			if ($finalScope === null) {
				$finalScope = $scope;
			}

			return new StatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPoints, $throwPoints);
		} elseif ($stmt instanceof Node\Stmt\TraitUse) {
			$hasYield = false;
			$throwPoints = [];
			$this->processTraitUse($stmt, $scope, $nodeCallback);
		} elseif ($stmt instanceof Foreach_) {
			$condResult = $this->processExprNode($stmt, $stmt->expr, $scope, $nodeCallback, ExpressionContext::createDeep());
			$throwPoints = $overridingThrowPoints ?? $condResult->getThrowPoints();
			$scope = $condResult->getScope();
			$arrayComparisonExpr = new BinaryOp\NotIdentical(
				$stmt->expr,
				new Array_([]),
			);
			if ($stmt->expr instanceof Variable && is_string($stmt->expr->name)) {
				$scope = $this->processVarAnnotation($scope, [$stmt->expr->name], $stmt);
			}
			$nodeCallback(new InForeachNode($stmt), $scope);
			$originalScope = $scope;
			$bodyScope = $scope;

			if ($context->isTopLevel()) {
				$originalScope = $this->polluteScopeWithAlwaysIterableForeach ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope;
				$bodyScope = $this->enterForeach($originalScope, $originalScope, $stmt);
				$count = 0;
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($this->polluteScopeWithAlwaysIterableForeach ? $scope->filterByTruthyValue($arrayComparisonExpr) : $scope);
					$bodyScope = $this->enterForeach($bodyScope, $originalScope, $stmt);
					$bodyScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, static function (): void {
					}, $context->enterDeep())->filterOutLoopExitPoints();
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
			$bodyScope = $this->enterForeach($bodyScope, $originalScope, $stmt);
			$finalScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, $nodeCallback, $context)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope();
			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
			}
			foreach ($finalScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
			}

			$exprType = $scope->getType($stmt->expr);
			$isIterableAtLeastOnce = $exprType->isIterableAtLeastOnce();
			if ($exprType->isIterable()->no() || $isIterableAtLeastOnce->maybe()) {
				if ($this->polluteScopeWithAlwaysIterableForeach) {
					$finalScope = $finalScope->mergeWith($scope->filterByTruthyValue(new BooleanOr(
						new BinaryOp\Identical(
							$stmt->expr,
							new Array_([]),
						),
						new FuncCall(new Name\FullyQualified('is_object'), [
							new Arg($stmt->expr),
						]),
					)));
				} else {
					$finalScope = $finalScope->mergeWith($scope);
				}
			} elseif ($isIterableAtLeastOnce->no() || $finalScopeResult->isAlwaysTerminating()) {
				$finalScope = $scope;
			} elseif (!$this->polluteScopeWithAlwaysIterableForeach) {
				$finalScope = $scope->processAlwaysIterableForeachScopeWithoutPollute($finalScope);
				// get types from finalScope, but don't create new variables
			}

			if (!$isIterableAtLeastOnce->no()) {
				$throwPoints = array_merge($throwPoints, $finalScopeResult->getThrowPoints());
			}
			if (!(new ObjectType(Traversable::class))->isSuperTypeOf($scope->getType($stmt->expr))->no()) {
				$throwPoints[] = ThrowPoint::createImplicit($scope, $stmt->expr);
			}

			return new StatementResult(
				$finalScope,
				$finalScopeResult->hasYield() || $condResult->hasYield(),
				$isIterableAtLeastOnce->yes() && $finalScopeResult->isAlwaysTerminating(),
				$finalScopeResult->getExitPointsForOuterLoop(),
				$throwPoints,
			);
		} elseif ($stmt instanceof While_) {
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, static function (): void {
			}, ExpressionContext::createDeep());
			$bodyScope = $condResult->getTruthyScope();

			if ($context->isTopLevel()) {
				$count = 0;
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($scope);
					$bodyScope = $this->processExprNode($stmt, $stmt->cond, $bodyScope, static function (): void {
					}, ExpressionContext::createDeep())->getTruthyScope();
					$bodyScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, static function (): void {
					}, $context->enterDeep())->filterOutLoopExitPoints();
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
			$bodyScope = $this->processExprNode($stmt, $stmt->cond, $bodyScope, $nodeCallback, ExpressionContext::createDeep())->getTruthyScope();
			$finalScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, $nodeCallback, $context)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope()->filterByFalseyValue($stmt->cond);
			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$finalScope = $finalScope->mergeWith($continueExitPoint->getScope());
			}
			$breakExitPoints = $finalScopeResult->getExitPointsByType(Break_::class);
			foreach ($breakExitPoints as $breakExitPoint) {
				$finalScope = $finalScope->mergeWith($breakExitPoint->getScope());
			}

			$beforeCondBooleanType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($stmt->cond) : $scope->getNativeType($stmt->cond))->toBoolean();
			$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $bodyScopeMaybeRan->getType($stmt->cond) : $bodyScopeMaybeRan->getNativeType($stmt->cond))->toBoolean();
			$isIterableAtLeastOnce = $beforeCondBooleanType->isTrue()->yes();
			$alwaysIterates = $condBooleanType->isTrue()->yes() && $context->isTopLevel();
			$neverIterates = $condBooleanType->isFalse()->yes() && $context->isTopLevel();
			$nodeCallback(new BreaklessWhileLoopNode($stmt, $finalScopeResult->getExitPoints()), $bodyScopeMaybeRan);

			if ($alwaysIterates) {
				$isAlwaysTerminating = count($finalScopeResult->getExitPointsByType(Break_::class)) === 0;
			} elseif ($isIterableAtLeastOnce) {
				$isAlwaysTerminating = $finalScopeResult->isAlwaysTerminating();
			} else {
				$isAlwaysTerminating = false;
			}
			$condScope = $condResult->getFalseyScope();
			if (!$isIterableAtLeastOnce) {
				if (!$this->polluteScopeWithLoopInitialAssignments) {
					$condScope = $condScope->mergeWith($scope);
				}
				$finalScope = $finalScope->mergeWith($condScope);
			}

			$throwPoints = $overridingThrowPoints ?? $condResult->getThrowPoints();
			if (!$neverIterates) {
				$throwPoints = array_merge($throwPoints, $finalScopeResult->getThrowPoints());
			}

			return new StatementResult(
				$finalScope,
				$finalScopeResult->hasYield() || $condResult->hasYield(),
				$isAlwaysTerminating,
				$finalScopeResult->getExitPointsForOuterLoop(),
				$throwPoints,
			);
		} elseif ($stmt instanceof Do_) {
			$finalScope = null;
			$bodyScope = $scope;
			$count = 0;
			$hasYield = false;
			$throwPoints = [];

			if ($context->isTopLevel()) {
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($scope);
					$bodyScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, static function (): void {
					}, $context->enterDeep())->filterOutLoopExitPoints();
					$alwaysTerminating = $bodyScopeResult->isAlwaysTerminating();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}
					$finalScope = $alwaysTerminating ? $finalScope : $bodyScope->mergeWith($finalScope);
					foreach ($bodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
						$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
					}
					$bodyScope = $this->processExprNode($stmt, $stmt->cond, $bodyScope, static function (): void {
					}, ExpressionContext::createDeep())->getTruthyScope();
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

			$bodyScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, $nodeCallback, $context)->filterOutLoopExitPoints();
			$bodyScope = $bodyScopeResult->getScope();
			foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
			}
			$condBooleanType = ($this->treatPhpDocTypesAsCertain ? $bodyScope->getType($stmt->cond) : $bodyScope->getNativeType($stmt->cond))->toBoolean();
			$alwaysIterates = $condBooleanType->isTrue()->yes() && $context->isTopLevel();

			$nodeCallback(new DoWhileLoopConditionNode($stmt->cond, $bodyScopeResult->getExitPoints()), $bodyScope);

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
				$condResult = $this->processExprNode($stmt, $stmt->cond, $bodyScope, $nodeCallback, ExpressionContext::createDeep());
				$hasYield = $condResult->hasYield();
				$throwPoints = $condResult->getThrowPoints();
				$finalScope = $condResult->getFalseyScope();
			} else {
				$this->processExprNode($stmt, $stmt->cond, $bodyScope, $nodeCallback, ExpressionContext::createDeep());
			}
			foreach ($bodyScopeResult->getExitPointsByType(Break_::class) as $breakExitPoint) {
				$finalScope = $breakExitPoint->getScope()->mergeWith($finalScope);
			}

			return new StatementResult(
				$finalScope,
				$bodyScopeResult->hasYield() || $hasYield,
				$alwaysTerminating,
				$bodyScopeResult->getExitPointsForOuterLoop(),
				array_merge($throwPoints, $bodyScopeResult->getThrowPoints()),
			);
		} elseif ($stmt instanceof For_) {
			$initScope = $scope;
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->init as $initExpr) {
				$initResult = $this->processExprNode($stmt, $initExpr, $initScope, $nodeCallback, ExpressionContext::createTopLevel());
				$initScope = $initResult->getScope();
				$hasYield = $hasYield || $initResult->hasYield();
				$throwPoints = array_merge($throwPoints, $initResult->getThrowPoints());
			}

			$bodyScope = $initScope;
			$isIterableAtLeastOnce = TrinaryLogic::createYes();
			foreach ($stmt->cond as $condExpr) {
				$condResult = $this->processExprNode($stmt, $condExpr, $bodyScope, static function (): void {
				}, ExpressionContext::createDeep());
				$initScope = $condResult->getScope();
				$condResultScope = $condResult->getScope();
				$condTruthiness = ($this->treatPhpDocTypesAsCertain ? $condResultScope->getType($condExpr) : $condResultScope->getNativeType($condExpr))->toBoolean();
				if ($condTruthiness instanceof ConstantBooleanType) {
					$condTruthinessTrinary = TrinaryLogic::createFromBoolean($condTruthiness->getValue());
				} else {
					$condTruthinessTrinary = TrinaryLogic::createMaybe();
				}
				$isIterableAtLeastOnce = $isIterableAtLeastOnce->and($condTruthinessTrinary);
				$hasYield = $hasYield || $condResult->hasYield();
				$throwPoints = array_merge($throwPoints, $condResult->getThrowPoints());
				$bodyScope = $condResult->getTruthyScope();
			}

			if ($context->isTopLevel()) {
				$count = 0;
				do {
					$prevScope = $bodyScope;
					$bodyScope = $bodyScope->mergeWith($initScope);
					foreach ($stmt->cond as $condExpr) {
						$bodyScope = $this->processExprNode($stmt, $condExpr, $bodyScope, static function (): void {
						}, ExpressionContext::createDeep())->getTruthyScope();
					}
					$bodyScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, static function (): void {
					}, $context->enterDeep())->filterOutLoopExitPoints();
					$bodyScope = $bodyScopeResult->getScope();
					foreach ($bodyScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
						$bodyScope = $bodyScope->mergeWith($continueExitPoint->getScope());
					}
					foreach ($stmt->loop as $loopExpr) {
						$exprResult = $this->processExprNode($stmt, $loopExpr, $bodyScope, static function (): void {
						}, ExpressionContext::createTopLevel());
						$bodyScope = $exprResult->getScope();
						$hasYield = $hasYield || $exprResult->hasYield();
						$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
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

			$bodyScope = $bodyScope->mergeWith($initScope);
			foreach ($stmt->cond as $condExpr) {
				$bodyScope = $this->processExprNode($stmt, $condExpr, $bodyScope, $nodeCallback, ExpressionContext::createDeep())->getTruthyScope();
			}

			$finalScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $bodyScope, $nodeCallback, $context)->filterOutLoopExitPoints();
			$finalScope = $finalScopeResult->getScope();
			foreach ($finalScopeResult->getExitPointsByType(Continue_::class) as $continueExitPoint) {
				$finalScope = $continueExitPoint->getScope()->mergeWith($finalScope);
			}

			$loopScope = $finalScope;
			foreach ($stmt->loop as $loopExpr) {
				$loopScope = $this->processExprNode($stmt, $loopExpr, $loopScope, $nodeCallback, ExpressionContext::createTopLevel())->getScope();
			}
			$finalScope = $finalScope->generalizeWith($loopScope);
			foreach ($stmt->cond as $condExpr) {
				$finalScope = $finalScope->filterByFalseyValue($condExpr);
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

			return new StatementResult(
				$finalScope,
				$finalScopeResult->hasYield() || $hasYield,
				false/* $finalScopeResult->isAlwaysTerminating() && $isAlwaysIterable*/,
				$finalScopeResult->getExitPointsForOuterLoop(),
				array_merge($throwPoints, $finalScopeResult->getThrowPoints()),
			);
		} elseif ($stmt instanceof Switch_) {
			$condResult = $this->processExprNode($stmt, $stmt->cond, $scope, $nodeCallback, ExpressionContext::createDeep());
			$scope = $condResult->getScope();
			$scopeForBranches = $scope;
			$finalScope = null;
			$prevScope = null;
			$hasDefaultCase = false;
			$alwaysTerminating = true;
			$hasYield = $condResult->hasYield();
			$exitPointsForOuterLoop = [];
			$throwPoints = $condResult->getThrowPoints();
			foreach ($stmt->cases as $caseNode) {
				if ($caseNode->cond !== null) {
					$condExpr = new BinaryOp\Equal($stmt->cond, $caseNode->cond);
					$caseResult = $this->processExprNode($stmt, $caseNode->cond, $scopeForBranches, $nodeCallback, ExpressionContext::createDeep());
					$scopeForBranches = $caseResult->getScope();
					$hasYield = $hasYield || $caseResult->hasYield();
					$throwPoints = array_merge($throwPoints, $caseResult->getThrowPoints());
					$branchScope = $caseResult->getTruthyScope()->filterByTruthyValue($condExpr);
				} else {
					$hasDefaultCase = true;
					$branchScope = $scopeForBranches;
				}

				$branchScope = $branchScope->mergeWith($prevScope);
				$branchScopeResult = $this->processStmtNodes($caseNode, $caseNode->stmts, $branchScope, $nodeCallback, $context);
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
				if ($branchScopeResult->isAlwaysTerminating()) {
					$alwaysTerminating = $alwaysTerminating && $branchFinalScopeResult->isAlwaysTerminating();
					$prevScope = null;
					if (isset($condExpr)) {
						$scopeForBranches = $scopeForBranches->filterByFalseyValue($condExpr);
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

			return new StatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPointsForOuterLoop, $throwPoints);
		} elseif ($stmt instanceof TryCatch) {
			$branchScopeResult = $this->processStmtNodes($stmt, $stmt->stmts, $scope, $nodeCallback, $context);
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
				$finallyExitPoints[] = $exitPoint;
				if ($exitPoint->getStatement() instanceof Node\Stmt\Expression && $exitPoint->getStatement()->expr instanceof Expr\Throw_) {
					continue;
				}
				if ($finallyScope !== null) {
					$finallyScope = $finallyScope->mergeWith($exitPoint->getScope());
				}
				$exitPoints[] = $exitPoint;
			}

			$throwPoints = $branchScopeResult->getThrowPoints();
			$throwPointsForLater = [];
			$pastCatchTypes = new NeverType();

			foreach ($stmt->catches as $catchNode) {
				$nodeCallback($catchNode, $scope);

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
							$matchingThrowPoints[$throwPointIndex] = $throwPoint;
						}
					}
				}

				// implicit only
				if (count($matchingThrowPoints) === 0) {
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
				if ($this->detectDeadTypeInMultiCatch) {
					foreach ($matchingCatchTypes as $catchTypeIndex => $matched) {
						if ($matched) {
							continue;
						}
						$nodeCallback(new CatchWithUnthrownExceptionNode($catchNode, $catchTypes[$catchTypeIndex], $originalCatchTypes[$catchTypeIndex]), $scope);
					}
				}

				if (count($matchingThrowPoints) === 0) {
					if (!$this->detectDeadTypeInMultiCatch) {
						$nodeCallback(new CatchWithUnthrownExceptionNode($catchNode, $catchType, $originalCatchType), $scope);
					}
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

				$catchScopeResult = $this->processStmtNodes($catchNode, $catchNode->stmts, $catchScope->enterCatchType($catchType, $variableName), $nodeCallback, $context);
				$catchScopeForFinally = $catchScopeResult->getScope();

				$finalScope = $catchScopeResult->isAlwaysTerminating() ? $finalScope : $catchScopeResult->getScope()->mergeWith($finalScope);
				$alwaysTerminating = $alwaysTerminating && $catchScopeResult->isAlwaysTerminating();
				$hasYield = $hasYield || $catchScopeResult->hasYield();
				$catchThrowPoints = $catchScopeResult->getThrowPoints();
				$throwPointsForLater = array_merge($throwPointsForLater, $catchThrowPoints);

				if ($finallyScope !== null) {
					$finallyScope = $finallyScope->mergeWith($catchScopeForFinally);
				}
				foreach ($catchScopeResult->getExitPoints() as $exitPoint) {
					$finallyExitPoints[] = $exitPoint;
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
				$finallyResult = $this->processStmtNodes($stmt->finally, $stmt->finally->stmts, $finallyScope, $nodeCallback, $context);
				$alwaysTerminating = $alwaysTerminating || $finallyResult->isAlwaysTerminating();
				$hasYield = $hasYield || $finallyResult->hasYield();
				$throwPointsForLater = array_merge($throwPointsForLater, $finallyResult->getThrowPoints());
				$finallyScope = $finallyResult->getScope();
				$finalScope = $finallyResult->isAlwaysTerminating() ? $finalScope : $finalScope->processFinallyScope($finallyScope, $originalFinallyScope);
				if (count($finallyResult->getExitPoints()) > 0) {
					$nodeCallback(new FinallyExitPointsNode(
						$finallyResult->getExitPoints(),
						$finallyExitPoints,
					), $scope);
				}
				$exitPoints = array_merge($exitPoints, $finallyResult->getExitPoints());
			}

			return new StatementResult($finalScope, $hasYield, $alwaysTerminating, $exitPoints, array_merge($throwPoints, $throwPointsForLater));
		} elseif ($stmt instanceof Unset_) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->vars as $var) {
				$scope = $this->lookForSetAllowedUndefinedExpressions($scope, $var);
				$scope = $this->processExprNode($stmt, $var, $scope, $nodeCallback, ExpressionContext::createDeep())->getScope();
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $var);
				$scope = $scope->unsetExpression($var);
			}
		} elseif ($stmt instanceof Node\Stmt\Use_) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->uses as $use) {
				$nodeCallback($use, $scope);
			}
		} elseif ($stmt instanceof Node\Stmt\Global_) {
			$hasYield = false;
			$throwPoints = [];
			$vars = [];
			foreach ($stmt->vars as $var) {
				if (!$var instanceof Variable) {
					throw new ShouldNotHappenException();
				}
				$scope = $this->lookForSetAllowedUndefinedExpressions($scope, $var);
				$this->processExprNode($stmt, $var, $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $var);

				if (!is_string($var->name)) {
					continue;
				}

				$scope = $scope->assignVariable($var->name, new MixedType(), new MixedType());
				$vars[] = $var->name;
			}
			$scope = $this->processVarAnnotation($scope, $vars, $stmt);
		} elseif ($stmt instanceof Static_) {
			$hasYield = false;
			$throwPoints = [];

			$vars = [];
			foreach ($stmt->vars as $var) {
				if (!is_string($var->var->name)) {
					throw new ShouldNotHappenException();
				}

				if ($var->default !== null) {
					$this->processExprNode($stmt, $var->default, $scope, $nodeCallback, ExpressionContext::createDeep());
				}

				$scope = $scope->enterExpressionAssign($var->var);
				$this->processExprNode($stmt, $var->var, $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $scope->exitExpressionAssign($var->var);

				$scope = $scope->assignVariable($var->var->name, new MixedType(), new MixedType());
				$vars[] = $var->var->name;
			}

			$scope = $this->processVarAnnotation($scope, $vars, $stmt);
		} elseif ($stmt instanceof Node\Stmt\Const_) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->consts as $const) {
				$nodeCallback($const, $scope);
				$this->processExprNode($stmt, $const->value, $scope, $nodeCallback, ExpressionContext::createDeep());
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
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $nodeCallback);
			foreach ($stmt->consts as $const) {
				$nodeCallback($const, $scope);
				$this->processExprNode($stmt, $const->value, $scope, $nodeCallback, ExpressionContext::createDeep());
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
			$this->processAttributeGroups($stmt, $stmt->attrGroups, $scope, $nodeCallback);
			if ($stmt->expr !== null) {
				$this->processExprNode($stmt, $stmt->expr, $scope, $nodeCallback, ExpressionContext::createDeep());
			}
		} elseif ($stmt instanceof Node\Stmt\Nop) {
			$hasYield = false;
			$throwPoints = $overridingThrowPoints ?? [];
		} elseif ($stmt instanceof Node\Stmt\GroupUse) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($stmt->uses as $use) {
				$nodeCallback($use, $scope);
			}
		} else {
			$hasYield = false;
			$throwPoints = $overridingThrowPoints ?? [];
		}

		return new StatementResult($scope, $hasYield, false, [], $throwPoints);
	}

	/**
	 * @return ThrowPoint[]|null
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

				return [ThrowPoint::createExplicit($scope, $throwsType, $statement, false)];
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

		return new ClassReflection(
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->fileTypeMapper,
			$this->stubPhpDocProvider,
			$this->phpDocInheritanceResolver,
			$this->phpVersion,
			$this->signatureMapProvider,
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getPropertiesClassReflectionExtensions(),
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getMethodsClassReflectionExtensions(),
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getAllowedSubTypesClassReflectionExtensions(),
			$betterReflectionClass->getName(),
			$betterReflectionClass instanceof ReflectionEnum && PHP_VERSION_ID >= 80000 ? new $enumAdapter($betterReflectionClass) : new ReflectionClass($betterReflectionClass),
			null,
			null,
			null,
			$this->universalObjectCratesClasses,
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
	public function processExprNode(Node\Stmt $stmt, Expr $expr, MutatingScope $scope, callable $nodeCallback, ExpressionContext $context): ExpressionResult
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

			return $this->processExprNode($stmt, $newExpr, $scope, $nodeCallback, $context);
		}

		$this->callNodeCallbackWithExpression($nodeCallback, $expr, $scope, $context);

		if ($expr instanceof Variable) {
			$hasYield = false;
			$throwPoints = [];
			if ($expr->name instanceof Expr) {
				return $this->processExprNode($stmt, $expr->name, $scope, $nodeCallback, $context->enterDeep());
			}
		} elseif ($expr instanceof Assign || $expr instanceof AssignRef) {
			$result = $this->processAssignVar(
				$scope,
				$stmt,
				$expr->var,
				$expr->expr,
				$nodeCallback,
				$context,
				function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context): ExpressionResult {
					if ($expr instanceof AssignRef) {
						$scope = $scope->enterExpressionAssign($expr->expr);
					}

					if ($expr->var instanceof Variable && is_string($expr->var->name)) {
						$context = $context->enterRightSideAssign(
							$expr->var->name,
							$scope->getType($expr->expr),
							$scope->getNativeType($expr->expr),
						);
					}

					$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
					$hasYield = $result->hasYield();
					$throwPoints = $result->getThrowPoints();
					$scope = $result->getScope();

					if ($expr instanceof AssignRef) {
						$scope = $scope->exitExpressionAssign($expr->expr);
					}

					return new ExpressionResult($scope, $hasYield, $throwPoints);
				},
				true,
			);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$vars = $this->getAssignedVariables($expr->var);
			if (count($vars) > 0) {
				$varChangedScope = false;
				$scope = $this->processVarAnnotation($scope, $vars, $stmt, $varChangedScope);
				if (!$varChangedScope) {
					$scope = $this->processStmtVarAnnotation($scope, $stmt, null, $nodeCallback);
				}
			}
		} elseif ($expr instanceof Expr\AssignOp) {
			$result = $this->processAssignVar(
				$scope,
				$stmt,
				$expr->var,
				$expr,
				$nodeCallback,
				$context,
				function (MutatingScope $scope) use ($stmt, $expr, $nodeCallback, $context): ExpressionResult {
					$originalScope = $scope;
					if ($expr instanceof Expr\AssignOp\Coalesce) {
						$scope = $scope->filterByFalseyValue(
							new BinaryOp\NotIdentical($expr->var, new ConstFetch(new Name('null'))),
						);
					}

					$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
					if ($expr instanceof Expr\AssignOp\Coalesce) {
						return new ExpressionResult(
							$result->getScope()->mergeWith($originalScope),
							$result->hasYield(),
							$result->getThrowPoints(),
						);
					}

					return $result;
				},
				$expr instanceof Expr\AssignOp\Coalesce,
			);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			if (
				($expr instanceof Expr\AssignOp\Div || $expr instanceof Expr\AssignOp\Mod) &&
				!$scope->getType($expr->expr)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
			) {
				$throwPoints[] = ThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
			}
		} elseif ($expr instanceof FuncCall) {
			$parametersAcceptor = null;
			$functionReflection = null;
			$throwPoints = [];
			if ($expr->name instanceof Expr) {
				$nameType = $scope->getType($expr->name);
				if ($nameType->isCallable()->yes()) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
						$scope,
						$expr->getArgs(),
						$nameType->getCallableParametersAcceptors($scope),
						null,
					);
				}

				$nameResult = $this->processExprNode($stmt, $expr->name, $scope, $nodeCallback, $context->enterDeep());
				$throwPoints = $nameResult->getThrowPoints();
				if (
					$nameType->isObject()->yes()
					&& $nameType->isCallable()->yes()
					&& (new ObjectType(Closure::class))->isSuperTypeOf($nameType)->no()
				) {
					$invokeResult = $this->processExprNode(
						$stmt,
						new MethodCall($expr->name, '__invoke', $expr->getArgs(), $expr->getAttributes()),
						$scope,
						static function (): void {
						},
						$context->enterDeep(),
					);
					$throwPoints = array_merge($throwPoints, $invokeResult->getThrowPoints());
				}
				$scope = $nameResult->getScope();
			} elseif ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$expr->getArgs(),
					$functionReflection->getVariants(),
					$functionReflection->getNamedArgumentsVariants(),
				);
			}

			if ($parametersAcceptor !== null) {
				$expr = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr) ?? $expr;
			}
			$result = $this->processArgs($stmt, $functionReflection, $parametersAcceptor, $expr->getArgs(), $scope, $nodeCallback, $context);
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());

			if ($functionReflection !== null) {
				$functionThrowPoint = $this->getFunctionThrowPoint($functionReflection, $parametersAcceptor, $expr, $scope);
				if ($functionThrowPoint !== null) {
					$throwPoints[] = $functionThrowPoint;
				}
			} else {
				$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
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
				&& count($expr->getArgs()) > 0
			) {
				$scope = $scope->invalidateExpression(new FuncCall(new Name('file_get_contents'), [$expr->getArgs()[0]]))
					->invalidateExpression(new FuncCall(new Name\FullyQualified('file_get_contents'), [$expr->getArgs()[0]]));
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['array_pop', 'array_shift'], true)
				&& count($expr->getArgs()) >= 1
			) {
				$arrayArg = $expr->getArgs()[0]->value;

				$arrayArgType = $scope->getType($arrayArg);
				$arrayArgNativeType = $scope->getNativeType($arrayArg);

				$isArrayPop = $functionReflection->getName() === 'array_pop';
				$scope = $scope->invalidateExpression($arrayArg)->assignExpression(
					$arrayArg,
					$isArrayPop ? $arrayArgType->popArray() : $arrayArgType->shiftArray(),
					$isArrayPop ? $arrayArgNativeType->popArray() : $arrayArgNativeType->shiftArray(),
				);
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['array_push', 'array_unshift'], true)
				&& count($expr->getArgs()) >= 2
			) {
				$arrayType = $this->getArrayFunctionAppendingType($functionReflection, $scope, $expr);
				$arrayNativeType = $this->getArrayFunctionAppendingType($functionReflection, $scope->doNotTreatPhpDocTypesAsCertain(), $expr);

				$arrayArg = $expr->getArgs()[0]->value;
				$scope = $scope->invalidateExpression($arrayArg)->assignExpression($arrayArg, $arrayType, $arrayNativeType);
			}

			if (
				$functionReflection !== null
				&& in_array($functionReflection->getName(), ['fopen', 'file_get_contents'], true)
			) {
				$scope = $scope->assignVariable('http_response_header', new ArrayType(new IntegerType(), new StringType()), new ArrayType(new IntegerType(), new StringType()));
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'shuffle'
			) {
				$arrayArg = $expr->getArgs()[0]->value;
				$scope = $scope->assignExpression(
					$arrayArg,
					$scope->getType($arrayArg)->shuffleArray(),
					$scope->getNativeType($arrayArg)->shuffleArray(),
				);
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'array_splice'
				&& count($expr->getArgs()) >= 1
			) {
				$arrayArg = $expr->getArgs()[0]->value;
				$arrayArgType = $scope->getType($arrayArg);
				$valueType = $arrayArgType->getIterableValueType();
				if (count($expr->getArgs()) >= 4) {
					$replacementType = $scope->getType($expr->getArgs()[3]->value)->toArray();
					$valueType = TypeCombinator::union($valueType, $replacementType->getIterableValueType());
				}
				$scope = $scope->invalidateExpression($arrayArg)->assignExpression(
					$arrayArg,
					new ArrayType($arrayArgType->getIterableKeyType(), $valueType),
					new ArrayType($arrayArgType->getIterableKeyType(), $valueType),
				);
			}

			if (
				$functionReflection !== null
				&& $functionReflection->getName() === 'extract'
			) {
				$extractedArg = $expr->getArgs()[0]->value;
				$extractedType = $scope->getType($extractedArg);
				if (count($extractedType->getConstantArrays()) > 0) {
					$properties = [];
					$optionalProperties = [];
					$refCount = [];
					foreach ($extractedType->getConstantArrays() as $constantArray) {
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
						$optional = in_array($name, $optionalProperties, true) || $refCount[$name] < count($extractedType->getConstantArrays());
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

			$result = $this->processExprNode($stmt, $expr->var, $closureCallScope ?? $scope, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$scope = $result->getScope();
			if (isset($closureCallScope)) {
				$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
			}
			$parametersAcceptor = null;
			$methodReflection = null;
			$calledOnType = $scope->getType($expr->var);
			if ($expr->name instanceof Expr) {
				$methodNameResult = $this->processExprNode($stmt, $expr->name, $scope, $nodeCallback, $context->enterDeep());
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

			if ($parametersAcceptor !== null) {
				$expr = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $expr) ?? $expr;
			}
			$result = $this->processArgs($stmt, $methodReflection, $parametersAcceptor, $expr->getArgs(), $scope, $nodeCallback, $context);
			$scope = $result->getScope();

			if ($methodReflection !== null) {
				$hasSideEffects = $methodReflection->hasSideEffects();
				if ($hasSideEffects->yes() || $methodReflection->getName() === '__construct') {
					$scope = $scope->invalidateExpression($expr->var, true);
				}
				if ($parametersAcceptor !== null) {
					$selfOutType = $methodReflection->getSelfOutType();
					if ($selfOutType !== null) {
						$scope = $scope->assignExpression(
							$expr->var,
							TemplateTypeHelper::resolveTemplateTypes(
								$selfOutType,
								$parametersAcceptor->getResolvedTemplateTypeMap(),
								$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
								TemplateTypeVariance::createCovariant(),
							),
							$scope->getNativeType($expr->var),
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
				$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
			}
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		} elseif ($expr instanceof Expr\NullsafeMethodCall) {
			$nonNullabilityResult = $this->ensureShallowNonNullability($scope, $scope, $expr->var);
			$exprResult = $this->processExprNode($stmt, new MethodCall($expr->var, $expr->name, $expr->args, array_merge($expr->getAttributes(), ['virtualNullsafeMethodCall' => true])), $nonNullabilityResult->getScope(), $nodeCallback, $context);
			$scope = $this->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

			return new ExpressionResult(
				$scope,
				$exprResult->hasYield(),
				$exprResult->getThrowPoints(),
				static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
		} elseif ($expr instanceof StaticCall) {
			$hasYield = false;
			$throwPoints = [];
			if ($expr->class instanceof Expr) {
				$objectClasses = $scope->getType($expr->class)->getObjectClassNames();
				if (count($objectClasses) !== 1) {
					$objectClasses = $scope->getType(new New_($expr->class))->getObjectClassNames();
				}
				if (count($objectClasses) === 1) {
					$objectExprResult = $this->processExprNode($stmt, new StaticCall(new Name($objectClasses[0]), $expr->name, []), $scope, static function (): void {
					}, $context->enterDeep());
					$additionalThrowPoints = $objectExprResult->getThrowPoints();
				} else {
					$additionalThrowPoints = [ThrowPoint::createImplicit($scope, $expr)];
				}
				$classResult = $this->processExprNode($stmt, $expr->class, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $classResult->hasYield();
				$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
				foreach ($additionalThrowPoints as $throwPoint) {
					$throwPoints[] = $throwPoint;
				}
				$scope = $classResult->getScope();
			}

			$parametersAcceptor = null;
			$methodReflection = null;
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$scope = $result->getScope();
			} elseif ($expr->class instanceof Name) {
				$className = $scope->resolveName($expr->class);
				if ($this->reflectionProvider->hasClass($className)) {
					$classReflection = $this->reflectionProvider->getClass($className);
					$methodName = $expr->name->name;
					if ($classReflection->hasMethod($methodName)) {
						$methodReflection = $classReflection->getMethod($methodName, $scope);
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
						if (
							$classReflection->getName() === 'Closure'
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

								$scopeClasses = [];
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
						$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
					}
				} else {
					$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
				}
			}

			if ($parametersAcceptor !== null) {
				$expr = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $expr) ?? $expr;
			}
			$result = $this->processArgs($stmt, $methodReflection, $parametersAcceptor, $expr->getArgs(), $scope, $nodeCallback, $context, $closureBindScope ?? null);
			$scope = $result->getScope();
			$scopeFunction = $scope->getFunction();

			if (
				$methodReflection !== null
				&& !$methodReflection->isStatic()
				&& (
					$methodReflection->hasSideEffects()->yes()
					|| $methodReflection->getName() === '__construct'
				)
				&& $scopeFunction instanceof MethodReflection
				&& !$scopeFunction->isStatic()
				&& $scope->isInClass()
				&& (
					$scope->getClassReflection()->getName() === $methodReflection->getDeclaringClass()->getName()
					|| $scope->getClassReflection()->isSubclassOf($methodReflection->getDeclaringClass()->getName())
				)
			) {
				$scope = $scope->invalidateExpression(new Variable('this'), true);
			}

			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		} elseif ($expr instanceof PropertyFetch) {
			$result = $this->processExprNode($stmt, $expr->var, $scope, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$scope = $result->getScope();
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof Expr\NullsafePropertyFetch) {
			$nonNullabilityResult = $this->ensureShallowNonNullability($scope, $scope, $expr->var);
			$exprResult = $this->processExprNode($stmt, new PropertyFetch($expr->var, $expr->name, array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch' => true])), $nonNullabilityResult->getScope(), $nodeCallback, $context);
			$scope = $this->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

			return new ExpressionResult(
				$scope,
				$exprResult->hasYield(),
				$exprResult->getThrowPoints(),
				static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
		} elseif ($expr instanceof StaticPropertyFetch) {
			$hasYield = false;
			$throwPoints = [];
			if ($expr->class instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->class, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$scope = $result->getScope();
			}
			if ($expr->name instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->name, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof Expr\Closure) {
			return $this->processClosureNode($stmt, $expr, $scope, $nodeCallback, $context, null);
		} elseif ($expr instanceof Expr\ArrowFunction) {
			return $this->processArrowFunctionNode($stmt, $expr, $scope, $nodeCallback, $context, null);
		} elseif ($expr instanceof ErrorSuppress) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$scope = $result->getScope();
		} elseif ($expr instanceof Exit_) {
			$hasYield = false;
			$throwPoints = [];
			if ($expr->expr !== null) {
				$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof Node\Scalar\InterpolatedString) {
			$hasYield = false;
			$throwPoints = [];
			foreach ($expr->parts as $part) {
				if (!$part instanceof Expr) {
					continue;
				}
				$result = $this->processExprNode($stmt, $part, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$scope = $result->getScope();
			}
		} elseif ($expr instanceof ArrayDimFetch) {
			$hasYield = false;
			$throwPoints = [];
			if ($expr->dim !== null) {
				$result = $this->processExprNode($stmt, $expr->dim, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				$scope = $result->getScope();
			}

			$result = $this->processExprNode($stmt, $expr->var, $scope, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$scope = $result->getScope();
		} elseif ($expr instanceof Array_) {
			$itemNodes = [];
			$hasYield = false;
			$throwPoints = [];
			foreach ($expr->items as $arrayItem) {
				$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
				$nodeCallback($arrayItem, $scope);
				if ($arrayItem->key !== null) {
					$keyResult = $this->processExprNode($stmt, $arrayItem->key, $scope, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $keyResult->hasYield();
					$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
					$scope = $keyResult->getScope();
				}

				$valueResult = $this->processExprNode($stmt, $arrayItem->value, $scope, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $valueResult->hasYield();
				$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
				$scope = $valueResult->getScope();
			}
			$nodeCallback(new LiteralArrayNode($expr, $itemNodes), $scope);
		} elseif ($expr instanceof BooleanAnd || $expr instanceof BinaryOp\LogicalAnd) {
			$leftResult = $this->processExprNode($stmt, $expr->left, $scope, $nodeCallback, $context->enterDeep());
			$rightResult = $this->processExprNode($stmt, $expr->right, $leftResult->getTruthyScope(), $nodeCallback, $context);
			$rightExprType = $rightResult->getScope()->getType($expr->right);
			if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
				$leftMergedWithRightScope = $leftResult->getFalseyScope();
			} else {
				$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
			}

			$this->callNodeCallbackWithExpression($nodeCallback, new BooleanAndNode($expr, $leftResult->getTruthyScope()), $scope, $context);

			return new ExpressionResult(
				$leftMergedWithRightScope,
				$leftResult->hasYield() || $rightResult->hasYield(),
				array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
				static fn (): MutatingScope => $rightResult->getScope()->filterByTruthyValue($expr),
				static fn (): MutatingScope => $leftMergedWithRightScope->filterByFalseyValue($expr),
			);
		} elseif ($expr instanceof BooleanOr || $expr instanceof BinaryOp\LogicalOr) {
			$leftResult = $this->processExprNode($stmt, $expr->left, $scope, $nodeCallback, $context->enterDeep());
			$rightResult = $this->processExprNode($stmt, $expr->right, $leftResult->getFalseyScope(), $nodeCallback, $context);
			$rightExprType = $rightResult->getScope()->getType($expr->right);
			if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
				$leftMergedWithRightScope = $leftResult->getTruthyScope();
			} else {
				$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
			}

			$this->callNodeCallbackWithExpression($nodeCallback, new BooleanOrNode($expr, $leftResult->getFalseyScope()), $scope, $context);

			return new ExpressionResult(
				$leftMergedWithRightScope,
				$leftResult->hasYield() || $rightResult->hasYield(),
				array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
				static fn (): MutatingScope => $leftMergedWithRightScope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $rightResult->getScope()->filterByFalseyValue($expr),
			);
		} elseif ($expr instanceof Coalesce) {
			$nonNullabilityResult = $this->ensureNonNullability($scope, $expr->left);
			$condScope = $this->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->left);
			$condResult = $this->processExprNode($stmt, $expr->left, $condScope, $nodeCallback, $context->enterDeep());
			$scope = $this->revertNonNullability($condResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());
			$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $expr->left);

			$rightScope = $scope->filterByFalseyValue($expr);
			$rightResult = $this->processExprNode($stmt, $expr->right, $rightScope, $nodeCallback, $context->enterDeep());
			$rightExprType = $scope->getType($expr->right);
			if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
				$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]));
			} else {
				$scope = $scope->filterByTruthyValue(new Expr\Isset_([$expr->left]))->mergeWith($rightResult->getScope());
			}

			$hasYield = $condResult->hasYield() || $rightResult->hasYield();
			$throwPoints = array_merge($condResult->getThrowPoints(), $rightResult->getThrowPoints());
		} elseif ($expr instanceof BinaryOp) {
			$result = $this->processExprNode($stmt, $expr->left, $scope, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$result = $this->processExprNode($stmt, $expr->right, $scope, $nodeCallback, $context->enterDeep());
			if (
				($expr instanceof BinaryOp\Div || $expr instanceof BinaryOp\Mod) &&
				!$scope->getType($expr->right)->toNumber()->isSuperTypeOf(new ConstantIntegerType(0))->no()
			) {
				$throwPoints[] = ThrowPoint::createExplicit($scope, new ObjectType(DivisionByZeroError::class), $expr, false);
			}
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		} elseif ($expr instanceof Expr\Include_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
			$hasYield = $result->hasYield();
			$scope = $result->getScope()->afterExtractCall();
		} elseif (
			$expr instanceof Expr\BitwiseNot
			|| $expr instanceof Cast
			|| $expr instanceof Expr\Clone_
			|| $expr instanceof Expr\Print_
			|| $expr instanceof Expr\UnaryMinus
			|| $expr instanceof Expr\UnaryPlus
		) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$hasYield = $result->hasYield();

			$scope = $result->getScope();
		} elseif ($expr instanceof Expr\Eval_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
			$hasYield = $result->hasYield();

			$scope = $result->getScope();
		} elseif ($expr instanceof Expr\YieldFrom) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
			$hasYield = true;

			$scope = $result->getScope();
		} elseif ($expr instanceof BooleanNot) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
		} elseif ($expr instanceof Expr\ClassConstFetch) {
			$hasYield = false;
			$throwPoints = [];
			if ($expr->class instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->class, $scope, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
			}
		} elseif ($expr instanceof Expr\Empty_) {
			$nonNullabilityResult = $this->ensureNonNullability($scope, $expr->expr);
			$scope = $this->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->expr);
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$scope = $this->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
			$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $expr->expr);
		} elseif ($expr instanceof Expr\Isset_) {
			$hasYield = false;
			$throwPoints = [];
			$nonNullabilityResults = [];
			foreach ($expr->vars as $var) {
				$nonNullabilityResult = $this->ensureNonNullability($scope, $var);
				$scope = $this->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $var);
				$result = $this->processExprNode($stmt, $var, $scope, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$nonNullabilityResults[] = $nonNullabilityResult;
			}
			foreach (array_reverse($expr->vars) as $var) {
				$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $var);
			}
			foreach (array_reverse($nonNullabilityResults) as $nonNullabilityResult) {
				$scope = $this->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
			}
		} elseif ($expr instanceof Instanceof_) {
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			if ($expr->class instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->class, $scope, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			}
		} elseif ($expr instanceof List_) {
			// only in assign and foreach, processed elsewhere
			return new ExpressionResult($scope, false, []);
		} elseif ($expr instanceof New_) {
			$parametersAcceptor = null;
			$constructorReflection = null;
			$hasYield = false;
			$throwPoints = [];
			if ($expr->class instanceof Expr) {
				$objectClasses = $scope->getType($expr)->getObjectClassNames();
				if (count($objectClasses) === 1) {
					$objectExprResult = $this->processExprNode($stmt, new New_(new Name($objectClasses[0])), $scope, static function (): void {
					}, $context->enterDeep());
					$additionalThrowPoints = $objectExprResult->getThrowPoints();
				} else {
					$additionalThrowPoints = [ThrowPoint::createImplicit($scope, $expr)];
				}

				$result = $this->processExprNode($stmt, $expr->class, $scope, $nodeCallback, $context->enterDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
				foreach ($additionalThrowPoints as $throwPoint) {
					$throwPoints[] = $throwPoint;
				}
			} elseif ($expr->class instanceof Class_) {
				$this->reflectionProvider->getAnonymousClassReflection($expr->class, $scope); // populates $expr->class->name
				$this->processStmtNode($expr->class, $scope, $nodeCallback, StatementContext::createTopLevel());
			} else {
				$className = $scope->resolveName($expr->class);
				if ($this->reflectionProvider->hasClass($className)) {
					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasConstructor()) {
						$constructorReflection = $classReflection->getConstructor();
						$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
							$scope,
							$expr->getArgs(),
							$constructorReflection->getVariants(),
							$constructorReflection->getNamedArgumentsVariants(),
						);
						$constructorThrowPoint = $this->getConstructorThrowPoint($constructorReflection, $parametersAcceptor, $classReflection, $expr, $expr->class, $expr->getArgs(), $scope);
						if ($constructorThrowPoint !== null) {
							$throwPoints[] = $constructorThrowPoint;
						}
					}
				} else {
					$throwPoints[] = ThrowPoint::createImplicit($scope, $expr);
				}
			}

			if ($parametersAcceptor !== null) {
				$expr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
			}
			$result = $this->processArgs($stmt, $constructorReflection, $parametersAcceptor, $expr->getArgs(), $scope, $nodeCallback, $context);
			$scope = $result->getScope();
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
		} elseif (
			$expr instanceof Expr\PreInc
			|| $expr instanceof Expr\PostInc
			|| $expr instanceof Expr\PreDec
			|| $expr instanceof Expr\PostDec
		) {
			$result = $this->processExprNode($stmt, $expr->var, $scope, $nodeCallback, $context->enterDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = [];

			$newExpr = $expr;
			if ($expr instanceof Expr\PostInc) {
				$newExpr = new Expr\PreInc($expr->var);
			} elseif ($expr instanceof Expr\PostDec) {
				$newExpr = new Expr\PreDec($expr->var);
			}

			$scope = $this->processAssignVar(
				$scope,
				$stmt,
				$expr->var,
				$newExpr,
				static function (Node $node, Scope $scope) use ($nodeCallback): void {
					if (!$node instanceof PropertyAssignNode) {
						return;
					}

					$nodeCallback($node, $scope);
				},
				$context,
				static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, false, []),
				false,
			)->getScope();
		} elseif ($expr instanceof Ternary) {
			$ternaryCondResult = $this->processExprNode($stmt, $expr->cond, $scope, $nodeCallback, $context->enterDeep());
			$throwPoints = $ternaryCondResult->getThrowPoints();
			$ifTrueScope = $ternaryCondResult->getTruthyScope();
			$ifFalseScope = $ternaryCondResult->getFalseyScope();
			$ifTrueType = null;
			if ($expr->if !== null) {
				$ifResult = $this->processExprNode($stmt, $expr->if, $ifTrueScope, $nodeCallback, $context);
				$throwPoints = array_merge($throwPoints, $ifResult->getThrowPoints());
				$ifTrueScope = $ifResult->getScope();
				$ifTrueType = $ifTrueScope->getType($expr->if);
			}

			$elseResult = $this->processExprNode($stmt, $expr->else, $ifFalseScope, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
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

			return new ExpressionResult(
				$finalScope,
				$ternaryCondResult->hasYield(),
				$throwPoints,
				static fn (): MutatingScope => $finalScope->filterByTruthyValue($expr),
				static fn (): MutatingScope => $finalScope->filterByFalseyValue($expr),
			);

		} elseif ($expr instanceof Expr\Yield_) {
			$throwPoints = [
				ThrowPoint::createImplicit($scope, $expr),
			];
			if ($expr->key !== null) {
				$keyResult = $this->processExprNode($stmt, $expr->key, $scope, $nodeCallback, $context->enterDeep());
				$scope = $keyResult->getScope();
				$throwPoints = $keyResult->getThrowPoints();
			}
			if ($expr->value !== null) {
				$valueResult = $this->processExprNode($stmt, $expr->value, $scope, $nodeCallback, $context->enterDeep());
				$scope = $valueResult->getScope();
				$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
			}
			$hasYield = true;
		} elseif ($expr instanceof Expr\Match_) {
			$deepContext = $context->enterDeep();
			$condResult = $this->processExprNode($stmt, $expr->cond, $scope, $nodeCallback, $deepContext);
			$scope = $condResult->getScope();
			$hasYield = $condResult->hasYield();
			$throwPoints = $condResult->getThrowPoints();
			$matchScope = $scope->enterMatch($expr);
			$armNodes = [];
			$hasDefaultCond = false;
			$hasAlwaysTrueCond = false;
			foreach ($expr->arms as $arm) {
				if ($arm->conds === null) {
					$hasDefaultCond = true;
					$matchArmBody = new MatchExpressionArmBody($matchScope, $arm->body);
					$armNodes[] = new MatchExpressionArm($matchArmBody, [], $arm->getStartLine());
					$armResult = $this->processExprNode($stmt, $arm->body, $matchScope, $nodeCallback, ExpressionContext::createTopLevel());
					$matchScope = $armResult->getScope();
					$hasYield = $hasYield || $armResult->hasYield();
					$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
					$scope = $scope->mergeWith($matchScope);
					continue;
				}

				if (count($arm->conds) === 0) {
					throw new ShouldNotHappenException();
				}

				$filteringExprs = [];
				$armCondScope = $matchScope;
				$condNodes = [];
				foreach ($arm->conds as $armCond) {
					$condNodes[] = new MatchExpressionArmCondition($armCond, $armCondScope, $armCond->getStartLine());
					$armCondResult = $this->processExprNode($stmt, $armCond, $armCondScope, $nodeCallback, $deepContext);
					$hasYield = $hasYield || $armCondResult->hasYield();
					$throwPoints = array_merge($throwPoints, $armCondResult->getThrowPoints());
					$armCondExpr = new BinaryOp\Identical($expr->cond, $armCond);
					$armCondResultScope = $armCondResult->getScope();
					$armCondType = $this->treatPhpDocTypesAsCertain ? $armCondResultScope->getType($armCondExpr) : $armCondResultScope->getNativeType($armCondExpr);
					if ($armCondType->isTrue()->yes()) {
						$hasAlwaysTrueCond = true;
					}
					$armCondScope = $armCondResult->getScope()->filterByFalseyValue($armCondExpr);
					$filteringExprs[] = $armCond;
				}

				if (count($filteringExprs) === 1) {
					$filteringExpr = new BinaryOp\Identical($expr->cond, $filteringExprs[0]);
				} else {
					$items = [];
					foreach ($filteringExprs as $filteringExpr) {
						$items[] = new Node\ArrayItem($filteringExpr);
					}
					$filteringExpr = new FuncCall(
						new Name\FullyQualified('in_array'),
						[
							new Arg($expr->cond),
							new Arg(new Array_($items)),
							new Arg(new ConstFetch(new Name\FullyQualified('true'))),
						],
					);
				}

				$bodyScope = $matchScope->filterByTruthyValue($filteringExpr);
				$matchArmBody = new MatchExpressionArmBody($bodyScope, $arm->body);
				$armNodes[] = new MatchExpressionArm($matchArmBody, $condNodes, $arm->getStartLine());

				$armResult = $this->processExprNode(
					$stmt,
					$arm->body,
					$bodyScope,
					$nodeCallback,
					ExpressionContext::createTopLevel(),
				);
				$armScope = $armResult->getScope();
				$scope = $scope->mergeWith($armScope);
				$hasYield = $hasYield || $armResult->hasYield();
				$throwPoints = array_merge($throwPoints, $armResult->getThrowPoints());
				$matchScope = $matchScope->filterByFalseyValue($filteringExpr);
			}

			$remainingType = $matchScope->getType($expr->cond);
			if (!$hasDefaultCond && !$hasAlwaysTrueCond && !$remainingType instanceof NeverType) {
				$throwPoints[] = ThrowPoint::createExplicit($scope, new ObjectType(UnhandledMatchError::class), $expr, false);
			}

			$nodeCallback(new MatchExpressionNode($expr->cond, $armNodes, $expr, $matchScope), $scope);
		} elseif ($expr instanceof AlwaysRememberedExpr) {
			$result = $this->processExprNode($stmt, $expr->getExpr(), $scope, $nodeCallback, $context);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$scope = $result->getScope();
		} elseif ($expr instanceof Expr\Throw_) {
			$hasYield = false;
			$result = $this->processExprNode($stmt, $expr->expr, $scope, $nodeCallback, ExpressionContext::createDeep());
			$throwPoints = $result->getThrowPoints();
			$throwPoints[] = ThrowPoint::createExplicit($scope, $scope->getType($expr->expr), $expr, false);
		} elseif ($expr instanceof FunctionCallableNode) {
			$throwPoints = [];
			$hasYield = false;
			if ($expr->getName() instanceof Expr) {
				$result = $this->processExprNode($stmt, $expr->getName(), $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $result->getScope();
				$hasYield = $result->hasYield();
				$throwPoints = $result->getThrowPoints();
			}
		} elseif ($expr instanceof MethodCallableNode) {
			$result = $this->processExprNode($stmt, $expr->getVar(), $scope, $nodeCallback, ExpressionContext::createDeep());
			$scope = $result->getScope();
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			if ($expr->getName() instanceof Expr) {
				$nameResult = $this->processExprNode($stmt, $expr->getVar(), $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $nameResult->getScope();
				$hasYield = $hasYield || $nameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			}
		} elseif ($expr instanceof StaticMethodCallableNode) {
			$throwPoints = [];
			$hasYield = false;
			if ($expr->getClass() instanceof Expr) {
				$classResult = $this->processExprNode($stmt, $expr->getClass(), $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $classResult->getScope();
				$hasYield = $classResult->hasYield();
				$throwPoints = $classResult->getThrowPoints();
			}
			if ($expr->getName() instanceof Expr) {
				$nameResult = $this->processExprNode($stmt, $expr->getName(), $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $nameResult->getScope();
				$hasYield = $hasYield || $nameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			}
		} elseif ($expr instanceof InstantiationCallableNode) {
			$throwPoints = [];
			$hasYield = false;
			if ($expr->getClass() instanceof Expr) {
				$classResult = $this->processExprNode($stmt, $expr->getClass(), $scope, $nodeCallback, ExpressionContext::createDeep());
				$scope = $classResult->getScope();
				$hasYield = $classResult->hasYield();
				$throwPoints = $classResult->getThrowPoints();
			}
		} elseif ($expr instanceof Node\Scalar) {
			$hasYield = false;
			$throwPoints = [];
		} else {
			$hasYield = false;
			$throwPoints = [];
		}

		return new ExpressionResult(
			$scope,
			$hasYield,
			$throwPoints,
			static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
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
					if (count($callArgType->getConstantArrays()) === 1) {
						$iterableValueTypes = $callArgType->getConstantArrays()[0]->getValueTypes();
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
					$constantArray = $constantArray->isIterableAtLeastOnce()->yes()
						? TypeCombinator::intersect($array, new NonEmptyArrayType())
						: $array;
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

	private function getFunctionThrowPoint(
		FunctionReflection $functionReflection,
		?ParametersAcceptor $parametersAcceptor,
		FuncCall $funcCall,
		MutatingScope $scope,
	): ?ThrowPoint
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

				return ThrowPoint::createExplicit($scope, $throwType, $funcCall, false);
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
				return ThrowPoint::createExplicit($scope, $throwType, $funcCall, true);
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
					return ThrowPoint::createImplicit($scope, $funcCall);
				}
			}
		}

		return null;
	}

	private function getMethodThrowPoint(MethodReflection $methodReflection, ParametersAcceptor $parametersAcceptor, MethodCall $methodCall, MutatingScope $scope): ?ThrowPoint
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

				return ThrowPoint::createExplicit($scope, $throwType, $methodCall, false);
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
				return ThrowPoint::createExplicit($scope, $throwType, $methodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $scope->getType($methodCall);
			if (!(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return ThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	/**
	 * @param Node\Arg[] $args
	 */
	private function getConstructorThrowPoint(MethodReflection $constructorReflection, ParametersAcceptor $parametersAcceptor, ClassReflection $classReflection, New_ $new, Name $className, array $args, MutatingScope $scope): ?ThrowPoint
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

				return ThrowPoint::createExplicit($scope, $throwType, $new, false);
			}
		}

		if ($constructorReflection->getThrowType() !== null) {
			$throwType = $constructorReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return ThrowPoint::createExplicit($scope, $throwType, $new, true);
			}
		} elseif ($this->implicitThrows) {
			if ($classReflection->getName() !== Throwable::class && !$classReflection->isSubclassOf(Throwable::class)) {
				return ThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
	}

	private function getStaticMethodThrowPoint(MethodReflection $methodReflection, ParametersAcceptor $parametersAcceptor, StaticCall $methodCall, MutatingScope $scope): ?ThrowPoint
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

				return ThrowPoint::createExplicit($scope, $throwType, $methodCall, false);
			}
		}

		if ($methodReflection->getThrowType() !== null) {
			$throwType = $methodReflection->getThrowType();
			if (!$throwType->isVoid()->yes()) {
				return ThrowPoint::createExplicit($scope, $throwType, $methodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $scope->getType($methodCall);
			if (!(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return ThrowPoint::createImplicit($scope, $methodCall);
			}
		}

		return null;
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
		ExpressionContext $context,
	): void
	{
		if ($context->isDeep()) {
			$scope = $scope->exitFirstLevelStatements();
		}
		$nodeCallback($expr, $scope);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processClosureNode(
		Node\Stmt $stmt,
		Expr\Closure $expr,
		MutatingScope $scope,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
	): ExpressionResult
	{
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $nodeCallback);
		}

		$byRefUses = [];

		$callableParameters = null;
		$closureCallArgs = $expr->getAttribute(ClosureArgVisitor::ATTRIBUTE_NAME);

		if ($closureCallArgs !== null) {
			$acceptors = $scope->getType($expr)->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 1) {
				$callableParameters = $acceptors[0]->getParameters();

				foreach ($callableParameters as $index => $callableParameter) {
					if (!isset($closureCallArgs[$index])) {
						continue;
					}

					$type = $scope->getType($closureCallArgs[$index]->value);
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
				$passedToType = TypeCombinator::union(...array_filter(
					$passedToType->getTypes(),
					static fn (Type $type) => $type->isCallable()->yes(),
				));
			}

			$acceptors = $passedToType->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 1) {
				$callableParameters = $acceptors[0]->getParameters();
			}
		}

		$useScope = $scope;
		foreach ($expr->uses as $use) {
			if ($use->byRef) {
				$byRefUses[] = $use;
				$useScope = $useScope->enterExpressionAssign($use->var);

				$inAssignRightSideVariableName = $context->getInAssignRightSideVariableName();
				$inAssignRightSideType = $context->getInAssignRightSideType();
				$inAssignRightSideNativeType = $context->getInAssignRightSideNativeType();
				if (
					$inAssignRightSideVariableName === $use->var->name
					&& $inAssignRightSideType !== null
					&& $inAssignRightSideNativeType !== null
				) {
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
					$scope = $scope->assignVariable($inAssignRightSideVariableName, $variableType, $variableNativeType);
				}
			}
			$this->processExprNode($stmt, $use->var, $useScope, $nodeCallback, $context);
			if (!$use->byRef) {
				continue;
			}

			$useScope = $useScope->exitExpressionAssign($use->var);
		}

		if ($expr->returnType !== null) {
			$nodeCallback($expr->returnType, $scope);
		}

		$closureScope = $scope->enterAnonymousFunction($expr, $callableParameters);
		$closureScope = $closureScope->processClosureScope($scope, null, $byRefUses);
		$closureType = $closureScope->getAnonymousFunctionReflection();
		if (!$closureType instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}

		$nodeCallback(new InClosureNode($closureType, $expr), $closureScope);

		$executionEnds = [];
		$gatheredReturnStatements = [];
		$gatheredYieldStatements = [];
		$closureStmtsCallback = static function (Node $node, Scope $scope) use ($nodeCallback, &$executionEnds, &$gatheredReturnStatements, &$gatheredYieldStatements, &$closureScope): void {
			$nodeCallback($node, $scope);
			if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
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
		};
		if (count($byRefUses) === 0) {
			$statementResult = $this->processStmtNodes($expr, $expr->stmts, $closureScope, $closureStmtsCallback, StatementContext::createTopLevel());
			$nodeCallback(new ClosureReturnStatementsNode(
				$expr,
				$gatheredReturnStatements,
				$gatheredYieldStatements,
				$statementResult,
				$executionEnds,
			), $closureScope);

			return new ExpressionResult($scope, false, []);
		}

		$count = 0;
		do {
			$prevScope = $closureScope;

			$intermediaryClosureScopeResult = $this->processStmtNodes($expr, $expr->stmts, $closureScope, static function (): void {
			}, StatementContext::createTopLevel());
			$intermediaryClosureScope = $intermediaryClosureScopeResult->getScope();
			foreach ($intermediaryClosureScopeResult->getExitPoints() as $exitPoint) {
				$intermediaryClosureScope = $intermediaryClosureScope->mergeWith($exitPoint->getScope());
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

		$statementResult = $this->processStmtNodes($expr, $expr->stmts, $closureScope, $closureStmtsCallback, StatementContext::createTopLevel());
		$nodeCallback(new ClosureReturnStatementsNode(
			$expr,
			$gatheredReturnStatements,
			$gatheredYieldStatements,
			$statementResult,
			$executionEnds,
		), $closureScope);

		return new ExpressionResult($scope->processClosureScope($closureScope, null, $byRefUses), false, []);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processArrowFunctionNode(
		Node\Stmt $stmt,
		Expr\ArrowFunction $expr,
		MutatingScope $scope,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $passedToType,
	): ExpressionResult
	{
		foreach ($expr->params as $param) {
			$this->processParamNode($stmt, $param, $scope, $nodeCallback);
		}
		if ($expr->returnType !== null) {
			$nodeCallback($expr->returnType, $scope);
		}

		$callableParameters = null;
		$arrowFunctionCallArgs = $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME);

		if ($arrowFunctionCallArgs !== null) {
			$acceptors = $scope->getType($expr)->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 1) {
				$callableParameters = $acceptors[0]->getParameters();

				foreach ($callableParameters as $index => $callableParameter) {
					if (!isset($arrowFunctionCallArgs[$index])) {
						continue;
					}

					$type = $scope->getType($arrowFunctionCallArgs[$index]->value);
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
				$passedToType = TypeCombinator::union(...array_filter(
					$passedToType->getTypes(),
					static fn (Type $type) => $type->isCallable()->yes(),
				));
			}

			$acceptors = $passedToType->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 1) {
				$callableParameters = $acceptors[0]->getParameters();
			}
		}

		$arrowFunctionScope = $scope->enterArrowFunction($expr, $callableParameters);
		$arrowFunctionType = $arrowFunctionScope->getAnonymousFunctionReflection();
		if (!$arrowFunctionType instanceof ClosureType) {
			throw new ShouldNotHappenException();
		}
		$nodeCallback(new InArrowFunctionNode($arrowFunctionType, $expr), $arrowFunctionScope);
		$this->processExprNode($stmt, $expr->expr, $arrowFunctionScope, $nodeCallback, ExpressionContext::createTopLevel());

		return new ExpressionResult($scope, false, []);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processParamNode(
		Node\Stmt $stmt,
		Node\Param $param,
		MutatingScope $scope,
		callable $nodeCallback,
	): void
	{
		$this->processAttributeGroups($stmt, $param->attrGroups, $scope, $nodeCallback);
		$nodeCallback($param, $scope);
		if ($param->type !== null) {
			$nodeCallback($param->type, $scope);
		}
		if ($param->default === null) {
			return;
		}

		$this->processExprNode($stmt, $param->default, $scope, $nodeCallback, ExpressionContext::createDeep());
	}

	/**
	 * @param AttributeGroup[] $attrGroups
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processAttributeGroups(
		Node\Stmt $stmt,
		array $attrGroups,
		MutatingScope $scope,
		callable $nodeCallback,
	): void
	{
		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				foreach ($attr->args as $arg) {
					$this->processExprNode($stmt, $arg->value, $scope, $nodeCallback, ExpressionContext::createDeep());
					$nodeCallback($arg, $scope);
				}
				$nodeCallback($attr, $scope);
			}
			$nodeCallback($attrGroup, $scope);
		}
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 * @param Node\Arg[] $args
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processArgs(
		Node\Stmt $stmt,
		$calleeReflection,
		?ParametersAcceptor $parametersAcceptor,
		array $args,
		MutatingScope $scope,
		callable $nodeCallback,
		ExpressionContext $context,
		?MutatingScope $closureBindScope = null,
	): ExpressionResult
	{
		$paramOutTypes = [];
		if ($parametersAcceptor !== null) {
			$parameters = $parametersAcceptor->getParameters();

			foreach ($parameters as $parameter) {
				if (!$parameter instanceof ParameterReflectionWithPhpDocs) {
					continue;
				}

				if ($parameter->getOutType() === null) {
					continue;
				}

				$paramOutTypes[$parameter->getName()] = TemplateTypeHelper::resolveTemplateTypes(
					$parameter->getOutType(),
					$parametersAcceptor->getResolvedTemplateTypeMap(),
					$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					TemplateTypeVariance::createCovariant(),
				);
			}
		}

		if ($calleeReflection !== null) {
			$scope = $scope->pushInFunctionCall($calleeReflection);
		}

		$hasYield = false;
		$throwPoints = [];
		foreach ($args as $i => $arg) {
			$assignByReference = false;
			if (isset($parameters) && $parametersAcceptor !== null) {
				if (isset($parameters[$i])) {
					$assignByReference = $parameters[$i]->passedByReference()->createsNewVariable();
					$parameterType = $parameters[$i]->getType();
				} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
					$lastParameter = $parameters[count($parameters) - 1];
					$assignByReference = $lastParameter->passedByReference()->createsNewVariable();
					$parameterType = $lastParameter->getType();
				}
			}

			if ($assignByReference) {
				if ($arg->value instanceof Variable) {
					$scope = $this->lookForSetAllowedUndefinedExpressions($scope, $arg->value);
				}
			}

			$originalArg = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
			$nodeCallback($originalArg, $scope);

			$originalScope = $scope;
			$scopeToPass = $scope;
			if ($i === 0 && $closureBindScope !== null) {
				$scopeToPass = $closureBindScope;
			}

			if ($arg->value instanceof Expr\Closure) {
				$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $context);
				$result = $this->processClosureNode($stmt, $arg->value, $scopeToPass, $nodeCallback, $context, $parameterType ?? null);
			} elseif ($arg->value instanceof Expr\ArrowFunction) {
				$this->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $context);
				$result = $this->processArrowFunctionNode($stmt, $arg->value, $scopeToPass, $nodeCallback, $context, $parameterType ?? null);
			} else {
				$result = $this->processExprNode($stmt, $arg->value, $scopeToPass, $nodeCallback, $context->enterDeep());
			}
			$scope = $result->getScope();
			if ($assignByReference) {
				if ($arg->value instanceof Variable) {
					$scope = $this->lookForUnsetAllowedUndefinedExpressions($scope, $arg->value);
				}
			}
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			if ($i !== 0 || $closureBindScope === null) {
				continue;
			}

			$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
		}

		if ($calleeReflection !== null) {
			$scope = $scope->popInFunctionCall();
		}

		foreach ($args as $i => $arg) {
			if (!isset($parameters) || $parametersAcceptor === null) {
				continue;
			}

			$byRefType = new MixedType();
			$assignByReference = false;
			if (isset($parameters[$i])) {
				$assignByReference = $parameters[$i]->passedByReference()->createsNewVariable();
				if (isset($paramOutTypes[$parameters[$i]->getName()])) {
					$byRefType = $paramOutTypes[$parameters[$i]->getName()];
				}
			} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
				$lastParameter = $parameters[count($parameters) - 1];
				$assignByReference = $lastParameter->passedByReference()->createsNewVariable();
				if (isset($paramOutTypes[$lastParameter->getName()])) {
					$byRefType = $paramOutTypes[$lastParameter->getName()];
				}
			}

			if ($assignByReference) {
				$argValue = $arg->value;
				if ($argValue instanceof Variable && is_string($argValue->name)) {
					$scope = $scope->assignVariable($argValue->name, $byRefType, new MixedType());
				} else {
					$scope = $scope->invalidateExpression($argValue);
				}
			} elseif ($calleeReflection !== null && $calleeReflection->hasSideEffects()->yes()) {
				$argType = $scope->getType($arg->value);
				if (!$argType->isObject()->no() || !(new ResourceType())->isSuperTypeOf($argType)->no()) {
					$scope = $scope->invalidateExpression($arg->value, true);
				}
			}
		}

		return new ExpressionResult($scope, $hasYield, $throwPoints);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @param Closure(MutatingScope $scope): ExpressionResult $processExprCallback
	 */
	private function processAssignVar(
		MutatingScope $scope,
		Node\Stmt $stmt,
		Expr $var,
		Expr $assignedExpr,
		callable $nodeCallback,
		ExpressionContext $context,
		Closure $processExprCallback,
		bool $enterExpressionAssign,
	): ExpressionResult
	{
		$nodeCallback($var, $enterExpressionAssign ? $scope->enterExpressionAssign($var) : $scope);
		$hasYield = false;
		$throwPoints = [];
		$isAssignOp = $assignedExpr instanceof Expr\AssignOp && !$enterExpressionAssign;
		if ($var instanceof Variable && is_string($var->name)) {
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$assignedExpr = $this->unwrapAssign($assignedExpr);
			$type = $scope->getType($assignedExpr);

			$conditionalExpressions = [];
			if ($assignedExpr instanceof Ternary) {
				$if = $assignedExpr->if;
				if ($if === null) {
					$if = $assignedExpr->cond;
				}
				$condScope = $this->processExprNode($stmt, $assignedExpr->cond, $scope, static function (): void {
				}, ExpressionContext::createDeep())->getScope();
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

			$truthySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $assignedExpr, TypeSpecifierContext::createTruthy());
			$falseySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $assignedExpr, TypeSpecifierContext::createFalsey());

			$truthyType = TypeCombinator::removeFalsey($type);
			$falseyType = TypeCombinator::intersect($type, StaticTypeFactory::falsey());

			$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType);
			$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType);
			$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType);
			$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($scope, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType);

			$scope = $result->getScope()->assignVariable($var->name, $type, $scope->getNativeType($assignedExpr));
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
				$assignedPropertyExpr = new SetOffsetValueTypeExpr(
					$varForSetOffsetValue,
					$var->dim,
					$assignedPropertyExpr,
				);
				$dimFetchStack[] = $var;
				$var = $var->var;
			}

			// 1. eval root expr
			if ($enterExpressionAssign) {
				$scope = $scope->enterExpressionAssign($var);
			}
			$result = $this->processExprNode($stmt, $var, $scope, $nodeCallback, $context->enterDeep());
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
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
					$nodeCallback($dimFetch, $enterExpressionAssign ? $scope->enterExpressionAssign($dimFetch) : $scope);
				}

				if ($dimExpr === null) {
					$offsetTypes[] = null;
					$offsetNativeTypes[] = null;

				} else {
					$offsetTypes[] = $scope->getType($dimExpr);
					$offsetNativeTypes[] = $scope->getNativeType($dimExpr);

					if ($enterExpressionAssign) {
						$scope->enterExpressionAssign($dimExpr);
					}
					$result = $this->processExprNode($stmt, $dimExpr, $scope, $nodeCallback, $context->enterDeep());
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
			$originalValueToWrite = $valueToWrite;
			$originalNativeValueToWrite = $valueToWrite;

			// 3. eval assigned expr
			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$scope = $result->getScope();

			$varType = $scope->getType($var);
			$varNativeType = $scope->getNativeType($var);

			// 4. compose types
			if ($varType instanceof ErrorType) {
				$varType = new ConstantArrayType([], []);
			}
			if ($varNativeType instanceof ErrorType) {
				$varNativeType = new ConstantArrayType([], []);
			}
			$offsetValueType = $varType;
			$offsetNativeValueType = $varNativeType;
			$offsetValueTypeStack = [$offsetValueType];
			$offsetValueNativeTypeStack = [$offsetNativeValueType];
			foreach (array_slice($offsetTypes, 0, -1) as $offsetType) {
				if ($offsetType === null) {
					$offsetValueType = new ConstantArrayType([], []);

				} else {
					$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
					if ($offsetValueType instanceof ErrorType) {
						$offsetValueType = new ConstantArrayType([], []);
					}
				}

				$offsetValueTypeStack[] = $offsetValueType;
			}
			foreach (array_slice($offsetNativeTypes, 0, -1) as $offsetNativeType) {
				if ($offsetNativeType === null) {
					$offsetNativeValueType = new ConstantArrayType([], []);

				} else {
					$offsetNativeValueType = $offsetNativeValueType->getOffsetValueType($offsetNativeType);
					if ($offsetNativeValueType instanceof ErrorType) {
						$offsetNativeValueType = new ConstantArrayType([], []);
					}
				}

				$offsetValueNativeTypeStack[] = $offsetNativeValueType;
			}

			foreach (array_reverse($offsetTypes) as $i => $offsetType) {
				/** @var Type $offsetValueType */
				$offsetValueType = array_pop($offsetValueTypeStack);
				if (!$offsetValueType instanceof MixedType) {
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
				$valueToWrite = $offsetValueType->setOffsetValueType($offsetType, $valueToWrite, $i === 0);
			}
			foreach (array_reverse($offsetNativeTypes) as $i => $offsetNativeType) {
				/** @var Type $offsetNativeValueType */
				$offsetNativeValueType = array_pop($offsetValueNativeTypeStack);
				if (!$offsetNativeValueType instanceof MixedType) {
					$types = [
						new ArrayType(new MixedType(), new MixedType()),
						new ObjectType(ArrayAccess::class),
						new NullType(),
					];
					if ($offsetNativeType !== null && $offsetNativeType->isInteger()->yes()) {
						$types[] = new StringType();
					}
					$offsetNativeValueType = TypeCombinator::intersect($offsetNativeValueType, TypeCombinator::union(...$types));
				}
				$nativeValueToWrite = $offsetNativeValueType->setOffsetValueType($offsetNativeType, $nativeValueToWrite, $i === 0);
			}

			if ($varType->isArray()->yes() || !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->yes()) {
				if ($var instanceof Variable && is_string($var->name)) {
					$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite);
				} else {
					if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
						$nodeCallback(new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scope);
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

				if ($originalVar->dim instanceof Variable || $originalVar->dim instanceof Node\Scalar) {
					$currentVarType = $scope->getType($originalVar);
					if (!$originalValueToWrite->isSuperTypeOf($currentVarType)->yes()) {
						$scope = $scope->assignExpression(
							$originalVar,
							$originalValueToWrite,
							$originalNativeValueToWrite,
						);
					}
				}
			} else {
				if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$nodeCallback(new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scope);
					if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
						$scope = $scope->assignInitializedProperty($scope->getType($var->var), $var->name->toString());
					}
				}
			}

			if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
				$throwPoints = array_merge($throwPoints, $this->processExprNode(
					$stmt,
					new MethodCall($var, 'offsetSet'),
					$scope,
					static function (): void {
					},
					$context,
				)->getThrowPoints());
			}
		} elseif ($var instanceof PropertyFetch) {
			$objectResult = $this->processExprNode($stmt, $var->var, $scope, $nodeCallback, $context);
			$hasYield = $objectResult->hasYield();
			$throwPoints = $objectResult->getThrowPoints();
			$scope = $objectResult->getScope();

			$propertyName = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $this->processExprNode($stmt, $var->name, $scope, $nodeCallback, $context);
				$hasYield = $hasYield || $propertyNameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $propertyNameResult->getThrowPoints());
				$scope = $propertyNameResult->getScope();
			}

			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$scope = $result->getScope();

			$propertyHolderType = $scope->getType($var->var);
			if ($propertyName !== null && $propertyHolderType->hasProperty($propertyName)->yes()) {
				$propertyReflection = $propertyHolderType->getProperty($propertyName, $scope);
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeCallback(new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scope);
				if ($propertyReflection->canChangeTypeAfterAssignment()) {
					$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
				}
				$declaringClass = $propertyReflection->getDeclaringClass();
				if ($declaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $declaringClass->getNativeProperty($propertyName);
					if (
						!$nativeProperty->getNativeType()->accepts($assignedExprType, true)->yes()
					) {
						$throwPoints[] = ThrowPoint::createExplicit($scope, new ObjectType(TypeError::class), $assignedExpr, false);
					}
					if ($enterExpressionAssign) {
						$scope = $scope->assignInitializedProperty($propertyHolderType, $propertyName);
					}
				}
			} else {
				// fallback
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeCallback(new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scope);
				$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
				// simulate dynamic property assign by __set to get throw points
				if (!$propertyHolderType->hasMethod('__set')->no()) {
					$throwPoints = array_merge($throwPoints, $this->processExprNode(
						$stmt,
						new MethodCall($var->var, '__set'),
						$scope,
						static function (): void {
						},
						$context,
					)->getThrowPoints());
				}
			}

		} elseif ($var instanceof Expr\StaticPropertyFetch) {
			if ($var->class instanceof Node\Name) {
				$propertyHolderType = $scope->resolveTypeByName($var->class);
			} else {
				$this->processExprNode($stmt, $var->class, $scope, $nodeCallback, $context);
				$propertyHolderType = $scope->getType($var->class);
			}

			$propertyName = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
				$hasYield = false;
				$throwPoints = [];
			} else {
				$propertyNameResult = $this->processExprNode($stmt, $var->name, $scope, $nodeCallback, $context);
				$hasYield = $propertyNameResult->hasYield();
				$throwPoints = $propertyNameResult->getThrowPoints();
				$scope = $propertyNameResult->getScope();
			}

			$result = $processExprCallback($scope);
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$scope = $result->getScope();

			if ($propertyName !== null) {
				$propertyReflection = $scope->getPropertyReflection($propertyHolderType, $propertyName);
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeCallback(new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scope);
				if ($propertyReflection !== null && $propertyReflection->canChangeTypeAfterAssignment()) {
					$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
				}
			} else {
				// fallback
				$assignedExprType = $scope->getType($assignedExpr);
				$nodeCallback(new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scope);
				$scope = $scope->assignExpression($var, $assignedExprType, $scope->getNativeType($assignedExpr));
			}
		} elseif ($var instanceof List_) {
			$result = $processExprCallback($scope);
			$hasYield = $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
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
				$nodeCallback($arrayItem, $itemScope);
				if ($arrayItem->key !== null) {
					$keyResult = $this->processExprNode($stmt, $arrayItem->key, $itemScope, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $keyResult->hasYield();
					$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
					$itemScope = $keyResult->getScope();
				}

				$valueResult = $this->processExprNode($stmt, $arrayItem->value, $itemScope, $nodeCallback, $context->enterDeep());
				$hasYield = $hasYield || $valueResult->hasYield();
				$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());

				if ($arrayItem->key === null) {
					$dimExpr = new Node\Scalar\Int_($i);
				} else {
					$dimExpr = $arrayItem->key;
				}
				$result = $this->processAssignVar(
					$scope,
					$stmt,
					$arrayItem->value,
					new GetOffsetValueTypeExpr($assignedExpr, $dimExpr),
					$nodeCallback,
					$context,
					static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, false, []),
					$enterExpressionAssign,
				);
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			}
		}

		return new ExpressionResult($scope, $hasYield, $throwPoints);
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
	private function processStmtVarAnnotation(MutatingScope $scope, Node\Stmt $stmt, ?Expr $defaultExpr, callable $nodeCallback): MutatingScope
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
					$nodeCallback(new VarTagChangedExpressionTypeNode($varTag, $variableNode), $scope);
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
				$nodeCallback(new VarTagChangedExpressionTypeNode($varTag, $defaultExpr), $scope);
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
			$scope = $scope->assignVariable($variableName, $variableType, new MixedType());
		}

		if (count($variableNames) === 1 && count($varTags) === 1 && isset($varTags[0])) {
			$variableType = $varTags[0]->getType();
			$changed = true;
			$scope = $scope->assignVariable($variableNames[0], $variableType, new MixedType());
		}

		return $scope;
	}

	private function enterForeach(MutatingScope $scope, MutatingScope $originalScope, Foreach_ $stmt): MutatingScope
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
			);
			$vars = [$stmt->valueVar->name];
			if ($keyVarName !== null) {
				$vars[] = $keyVarName;
			}
		} else {
			$scope = $this->processAssignVar(
				$scope,
				$stmt,
				$stmt->valueVar,
				new GetIterableValueTypeExpr($stmt->expr),
				static function (): void {
				},
				ExpressionContext::createDeep(),
				static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, false, []),
				true,
			)->getScope();
			$vars = $this->getAssignedVariables($stmt->valueVar);
			if (
				$stmt->keyVar instanceof Variable && is_string($stmt->keyVar->name)
			) {
				$scope = $scope->enterForeachKey($originalScope, $stmt->expr, $stmt->keyVar->name);
				$vars[] = $stmt->keyVar->name;
			} elseif ($stmt->keyVar !== null) {
				$scope = $this->processAssignVar(
					$scope,
					$stmt,
					$stmt->keyVar,
					new GetIterableKeyTypeExpr($stmt->expr),
					static function (): void {
					},
					ExpressionContext::createDeep(),
					static fn (MutatingScope $scope): ExpressionResult => new ExpressionResult($scope, false, []),
					true,
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

		return $this->processVarAnnotation($scope, $vars, $stmt);
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processTraitUse(Node\Stmt\TraitUse $node, MutatingScope $classScope, callable $nodeCallback): void
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
			$parserNodes = $this->parser->parseFile($fileName);
			$this->processNodesForTraitUse($parserNodes, $traitReflection, $classScope, $node->adaptations, $nodeCallback);
		}
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @param Node\Stmt\TraitUseAdaptation[] $adaptations
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processNodesForTraitUse($node, ClassReflection $traitReflection, MutatingScope $scope, array $adaptations, callable $nodeCallback): void
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

				$traitScope = $scope->enterTrait($traitReflection);
				$nodeCallback(new InTraitNode($node, $traitReflection), $traitScope);
				$this->processStmtNodes($node, $stmts, $traitScope, $nodeCallback, StatementContext::createTopLevel());
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
				$this->processNodesForTraitUse($subNode, $traitReflection, $scope, $adaptations, $nodeCallback);
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodesForTraitUse($subNode, $traitReflection, $scope, $adaptations, $nodeCallback);
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
		$this->processNodesForCalledMethod($parserNodes, $fileName, $methodReflection, static function (Node $node, Scope $scope) use ($methodReflection, &$returnStatement): void {
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
	private function processNodesForCalledMethod($node, string $fileName, MethodReflection $methodReflection, callable $nodeCallback): void
	{
		if ($node instanceof Node) {
			$declaringClass = $methodReflection->getDeclaringClass();
			if (
				$node instanceof Node\Stmt\Class_
				&& $node->namespacedName !== null
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
					$this->processStmtNode($stmt, $scope, $nodeCallback, StatementContext::createTopLevel());
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
				$this->processNodesForCalledMethod($subNode, $fileName, $methodReflection, $nodeCallback);
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodesForCalledMethod($subNode, $fileName, $methodReflection, $nodeCallback);
			}
		}
	}

	/**
	 * @return array{TemplateTypeMap, array<string, Type>, ?Type, ?Type, ?string, bool, bool, bool, bool|null, bool, bool, string|null, Assertions, ?Type, array<string, Type>, array<(string|int), VarTag>, bool}
	 */
	public function getPhpDocs(Scope $scope, Node\FunctionLike|Node\Stmt\Property $node): array
	{
		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTypes = [];
		$phpDocReturnType = null;
		$phpDocThrowType = null;
		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		$isPure = false;
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
		}

		return [$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $docComment, $asserts, $selfOutType, $phpDocParameterOutTypes, $varTags, $isAllowedPrivateMutation];
	}

	private function transformStaticType(ClassReflection $declaringClass, Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($declaringClass): Type {
			if ($type instanceof StaticType) {
				$changedType = $type->changeBaseClass($declaringClass);
				if ($declaringClass->isFinal()) {
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

		return null;
	}

	/**
	 * @template T of Node
	 * @param array<T> $nodes
	 * @return T|null
	 */
	private function getFirstUnreachableNode(array $nodes, bool $earlyBinding): ?Node
	{
		foreach ($nodes as $node) {
			if ($node instanceof Node\Stmt\Nop) {
				continue;
			}
			if ($earlyBinding && ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike)) {
				continue;
			}
			return $node;
		}
		return null;
	}

}
