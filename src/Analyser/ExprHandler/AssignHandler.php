<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\AssignTargetWalkMode;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\PreparedAssignTarget;
use PHPStan\Analyser\PropertyHookThrowPointsResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VarAnnotationProcessor;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Node\VirtualNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use TypeError;
use function array_key_last;
use function array_merge;
use function array_pop;
use function array_reverse;
use function array_slice;
use function count;
use function in_array;
use function is_int;
use function is_string;
use function spl_object_id;

/**
 * @implements ExprHandler<Assign|AssignRef>
 */
#[AutowiredService]
final class AssignHandler implements ExprHandler
{

	public function __construct(
		private VarAnnotationProcessor $varAnnotationProcessor,
		private PhpVersion $phpVersion,
		private ExprPrinter $exprPrinter,
		private MatchHandler $matchHandler,
		private TernaryHandler $ternaryHandler,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private IdenticalNarrowingHelper $identicalNarrowingHelper,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private VirtualExprResultHelper $virtualExprResultHelper,
		private NonNullabilityHelper $nonNullabilityHelper,
		private VariableHandler $variableHandler,
		private ArrayDimFetchHandler $arrayDimFetchHandler,
		private PropertyFetchHandler $propertyFetchHandler,
		private StaticPropertyFetchHandler $staticPropertyFetchHandler,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private PropertyHookThrowPointsResolver $propertyHookThrowPointsResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Assign || $expr instanceof AssignRef;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$target = $this->prepareTarget(
			$nodeScopeResolver,
			$scope,
			$storage,
			$stmt,
			$expr->var,
			$expr->expr,
			$nodeCallback,
			$context,
			AssignTargetWalkMode::assign(),
		);

		$valueBeforeScope = $target->getScope();
		$valueScope = $valueBeforeScope;
		$valueImpurePoints = [];
		if ($expr instanceof AssignRef) {
			$referencedExpr = $expr->expr;
			while ($referencedExpr instanceof ArrayDimFetch) {
				$referencedExpr = $referencedExpr->var;
			}

			if ($referencedExpr instanceof PropertyFetch || $referencedExpr instanceof StaticPropertyFetch) {
				$valueImpurePoints[] = new ImpurePoint(
					$valueScope,
					$expr,
					'propertyAssignByRef',
					'property assignment by reference',
					false,
				);
			}

			$valueScope = $valueScope->enterExpressionAssign($expr->expr);
		}

		$valueContext = $context;
		if ($expr->var instanceof Variable && is_string($expr->var->name)) {
			$valueContext = $valueContext->enterRightSideAssign(
				$expr->var->name,
				$expr->expr,
			);
		}

		$assignedExprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $valueScope, $storage, $nodeCallback, $valueContext->enterDeep());
		$valueImpurePoints = array_merge($valueImpurePoints, $assignedExprResult->getImpurePoints());
		$valueScope = $assignedExprResult->getScope();

		if ($expr instanceof AssignRef) {
			$valueScope = $valueScope->exitExpressionAssign($expr->expr);
		}

		$result = $this->applyWrite(
			$nodeScopeResolver,
			$target,
			$this->expressionResultFactory->create(
				$valueScope,
				beforeScope: $valueBeforeScope,
				expr: $expr->expr,
				hasYield: $assignedExprResult->hasYield(),
				isAlwaysTerminating: $assignedExprResult->isAlwaysTerminating(),
				throwPoints: $assignedExprResult->getThrowPoints(),
				impurePoints: $valueImpurePoints,
				typeCallback: static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $assignedExprResult->getNativeType() : $assignedExprResult->getType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			),
			$assignedExprResult,
			$stmt,
			$storage,
			$nodeCallback,
			$context,
		);
		$scope = $result->getScope();

		if (
			$expr instanceof AssignRef
			&& $expr->var instanceof Variable
			&& is_string($expr->var->name)
			&& $expr->expr instanceof Variable
			&& is_string($expr->expr->name)
		) {
			$varName = $expr->var->name;
			$refName = $expr->expr->name;
			// a plain variable read is scope state - no result or walk needed
			$type = $scope->hasVariableType($varName)->no() ? new ErrorType() : $scope->getVariableType($varName);
			$nativeScope = $scope->doNotTreatPhpDocTypesAsCertain();
			$nativeType = $nativeScope->hasVariableType($varName)->no() ? new ErrorType() : $nativeScope->getVariableType($varName);

			// When $varName is assigned, update $refName
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($varName, new Variable($refName), new Variable($varName)),
				$type,
				$nativeType,
			);

			// When $refName is assigned, update $varName
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($refName, new Variable($varName), new Variable($refName)),
				$type,
				$nativeType,
			);
		}

		$vars = $nodeScopeResolver->getAssignedVariables($expr->var);
		if (count($vars) > 0) {
			$varChangedScope = false;
			$scope = $this->varAnnotationProcessor->processVarAnnotation($scope, $vars, $stmt, $varChangedScope);
			if (!$varChangedScope) {
				$scope = $nodeScopeResolver->processStmtVarAnnotation($scope, $storage, $stmt, null, $nodeCallback);
			}
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: $result->isAlwaysTerminating(),
			throwPoints: $result->getThrowPoints(),
			impurePoints: $result->getImpurePoints(),
			typeCallback: static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $assignedExprResult->getNativeType() : $assignedExprResult->getType(),
			specifyTypesCallback: $expr instanceof Assign ? $this->createSpecifyTypesCallback($expr, $assignedExprResult, $beforeScope, $storage) : fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
			createTypesCallback: $expr instanceof Assign ? $this->createCreateTypesCallback($expr, $assignedExprResult, $beforeScope) : null,
		);
	}

	/**
	 * Results of a walked call's arguments (and of the count()-minus-one shape's
	 * call), keyed by the argument value expression - the sources the lazy
	 * assignment narrowing reads.
	 *
	 * @return array<int, ExpressionResult>
	 */
	private function captureAssignedCallArgResults(Expr $assignedExpr, ExpressionResultStorage $storage): array
	{
		$call = null;
		if ($assignedExpr instanceof FuncCall) {
			$call = $assignedExpr;
		} elseif ($assignedExpr instanceof Expr\BinaryOp\Minus && $assignedExpr->left instanceof FuncCall) {
			$call = $assignedExpr->left;
		}
		if ($call === null || $call->isFirstClassCallable()) {
			return [];
		}

		$argResults = [];
		foreach ($call->getArgs() as $arg) {
			$argResult = $storage->findExpressionResult($arg->value);
			if ($argResult === null) {
				continue;
			}

			$argResults[spl_object_id($arg->value)] = $argResult;
		}

		return $argResults;
	}

	/**
	 * A type constraint on an assignment constrains the assigned variable
	 * and the assigned expression - what TypeSpecifier::create() recovered
	 * by unwrapping assign chains. Nested assignments compose through the
	 * assigned expression's own result.
	 *
	 * @return Closure(Type, TypeSpecifierContext, bool): SpecifiedTypes
	 */
	private function createCreateTypesCallback(Assign $expr, ExpressionResult $assignedExprResult, MutatingScope $beforeScope): Closure
	{
		return function (Type $type, TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $assignedExprResult, $beforeScope): SpecifiedTypes {
			$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
			$types = $this->defaultNarrowingHelper->createSubjectTypes($s, $expr->var, null, $type, $context);

			return $types->unionWith(
				$this->defaultNarrowingHelper->createSubjectTypes($s, $expr->expr, $assignedExprResult, $type, $context),
			);
		};
	}

	/**
	 * New-world copy of the non-null contexts of specifyTypes(): the assigned
	 * variable narrows by the boolean outcome, plus the $arr[$key] inference
	 * after $key = array_key_first/array_key_last/array_search/array_find_key.
	 * The null-context inferences stay in specifyTypes() - result-based asks
	 * are always truthy or falsey.
	 *
	 * @return Closure(TypeSpecifierContext, bool): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(Assign $expr, ExpressionResult $assignedExprResult, MutatingScope $beforeScope, ExpressionResultStorage $storage): Closure
	{
		// the value expression's call arguments were walked as its children -
		// capture their results now so the lazy narrowing below reads them
		// instead of the storage
		$argResults = $this->captureAssignedCallArgResults($expr->expr, $storage);

		return function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $assignedExprResult, $beforeScope, $argResults): SpecifiedTypes {
			$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
			$argType = static function (Expr $e) use ($argResults, $s): Type {
				$result = $argResults[spl_object_id($e)] ?? null;
				if ($result !== null) {
					return $result->getTypeOnScope($s, $s->nativeTypesPromoted);
				}

				// every argument of the walked call has a captured result
				throw new ShouldNotHappenException();
			};
			if ($context->null()) {
				$assignedScope = $s->exitFirstLevelStatements();
				$specifiedTypes = $assignedExprResult->getSpecifiedTypesForScope($assignedScope, $context)->setRootExpr($expr);
				$specifiedTypes = $specifiedTypes->removeExpr($this->exprPrinter->printExpr($expr->var));
			} else {
				$specifiedTypes = $this->defaultNarrowingHelper->specifyDefaultTypes($expr->var, $context)->setRootExpr($expr);
			}

			// infer $arr[$key] after $key = array_key_first/last($arr)
			if (
				$expr->expr instanceof FuncCall
				&& $expr->expr->name instanceof Name
				&& !$expr->expr->isFirstClassCallable()
				&& in_array($expr->expr->name->toLowerString(), ['array_key_first', 'array_key_last'], true)
				&& count($expr->expr->getArgs()) >= 1
			) {
				$arrayArg = $expr->expr->getArgs()[0]->value;
				$arrayType = $argType($arrayArg);

				if ($arrayType->isArray()->yes()) {
					if ($context->true()) {
						$specifiedTypes = $specifiedTypes->unionWith(
							$this->defaultNarrowingHelper->createSubjectTypes($s, $arrayArg, null, new NonEmptyArrayType(), TypeSpecifierContext::createTrue()),
						);
						$isNonEmpty = true;
					} else {
						$isNonEmpty = $arrayType->isIterableAtLeastOnce()->yes();
					}

					if ($isNonEmpty) {
						$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);
						$specifiedTypes = $specifiedTypes->unionWith(
							$this->defaultNarrowingHelper->createSubjectTypes($s, $dimFetch, null, $arrayType->getIterableValueType(), TypeSpecifierContext::createTrue()),
						);
					} elseif ($expr->var instanceof Variable && is_string($expr->var->name)) {
						$keyType = $assignedExprResult->getTypeOnScope($s, $s->nativeTypesPromoted);
						$nonNullKeyType = TypeCombinator::removeNull($keyType);
						if (!$nonNullKeyType instanceof NeverType) {
							$specifiedTypes = $specifiedTypes->unionWith(
								$this->createArrayDimFetchConditionalExpressionHolder($expr->var, $arrayArg, $nonNullKeyType, $arrayType->getIterableValueType()),
							);
						}
					}
				}
			}

			// infer $arr[$key] after $key = array_search($needle, $arr) or $key = array_find_key($arr, $callback)
			if (
				$expr->expr instanceof FuncCall
				&& $expr->expr->name instanceof Name
				&& !$expr->expr->isFirstClassCallable()
				&& count($expr->expr->getArgs()) >= 2
			) {
				$funcName = $expr->expr->name->toLowerString();
				$arrayArg = null;
				$sentinelType = null;
				$isStrictArraySearch = false;

				if ($funcName === 'array_search') {
					$arrayArg = $expr->expr->getArgs()[1]->value;
					$sentinelType = new ConstantBooleanType(false);
					$isStrictArraySearch = count($expr->expr->getArgs()) >= 3 && $argType($expr->expr->getArgs()[2]->value)->isTrue()->yes();
				} elseif ($funcName === 'array_find_key') {
					$arrayArg = $expr->expr->getArgs()[0]->value;
					$sentinelType = new NullType();
				}

				if ($arrayArg !== null) {
					$arrayType = $argType($arrayArg);

					if ($arrayType->isArray()->yes()) {
						if ($context->true()) {
							$specifiedTypes = $specifiedTypes->unionWith(
								$this->defaultNarrowingHelper->createSubjectTypes($s, $arrayArg, null, new NonEmptyArrayType(), TypeSpecifierContext::createTrue()),
							);

							$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);

							if ($isStrictArraySearch) {
								$needleType = $argType($expr->expr->getArgs()[0]->value);
								$dimFetchType = TypeCombinator::intersect($needleType, $arrayType->getIterableValueType());
							} else {
								$dimFetchType = $arrayType->getIterableValueType();
							}

							$specifiedTypes = $specifiedTypes->unionWith(
								$this->defaultNarrowingHelper->createSubjectTypes($s, $dimFetch, null, $dimFetchType, TypeSpecifierContext::createTrue()),
							);
						} elseif ($expr->var instanceof Variable && is_string($expr->var->name)) {
							$keyType = $assignedExprResult->getTypeOnScope($s, $s->nativeTypesPromoted);
							$narrowedKeyType = TypeCombinator::remove($keyType, $sentinelType);
							if (!$narrowedKeyType instanceof NeverType) {
								if ($isStrictArraySearch) {
									$needleType = $argType($expr->expr->getArgs()[0]->value);
									$dimFetchType = TypeCombinator::intersect($needleType, $arrayType->getIterableValueType());
								} else {
									$dimFetchType = $arrayType->getIterableValueType();
								}
								$specifiedTypes = $specifiedTypes->unionWith(
									$this->createArrayDimFetchConditionalExpressionHolder($expr->var, $arrayArg, $narrowedKeyType, $dimFetchType),
								);
							}
						}
					}
				}
			}

			if ($context->null()) {
				// infer $arr[$key] after $key = array_rand($arr)
				if (
					$expr->expr instanceof FuncCall
					&& $expr->expr->name instanceof Name
					&& !$expr->expr->isFirstClassCallable()
					&& in_array($expr->expr->name->toLowerString(), ['array_rand'], true)
					&& count($expr->expr->getArgs()) >= 1
				) {
					$numArg = null;
					$args = $expr->expr->getArgs();
					$arrayArg = $args[0]->value;
					if (count($args) > 1) {
						$numArg = $args[1]->value;
					}
					$one = new ConstantIntegerType(1);
					$arrayType = $argType($arrayArg);

					if (
						$arrayType->isArray()->yes()
						&& $arrayType->isIterableAtLeastOnce()->yes()
						&& ($numArg === null || $one->isSuperTypeOf($argType($numArg))->yes())
					) {
						$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);

						return $specifiedTypes->unionWith(
							$this->defaultNarrowingHelper->createSubjectTypes($s, $dimFetch, null, $arrayType->getIterableValueType(), TypeSpecifierContext::createTrue()),
						);
					}
				}

				// infer $list[$count] after $count = count($list) - 1
				if (
					$expr->expr instanceof Expr\BinaryOp\Minus
					&& $expr->expr->left instanceof FuncCall
					&& $expr->expr->left->name instanceof Name
					&& !$expr->expr->left->isFirstClassCallable()
					&& $expr->expr->right instanceof Node\Scalar\Int_
					&& $expr->expr->right->value === 1
					&& in_array($expr->expr->left->name->toLowerString(), ['count', 'sizeof'], true)
					&& count($expr->expr->left->getArgs()) >= 1
				) {
					$arrayArg = $expr->expr->left->getArgs()[0]->value;
					$arrayType = $argType($arrayArg);
					if (
						$arrayType->isList()->yes()
						&& $arrayType->isIterableAtLeastOnce()->yes()
					) {
						$dimFetch = new ArrayDimFetch($arrayArg, $expr->var);

						return $specifiedTypes->unionWith(
							$this->defaultNarrowingHelper->createSubjectTypes($s, $dimFetch, null, $arrayType->getIterableValueType(), TypeSpecifierContext::createTrue()),
						);
					}
				}
			}

			return $specifiedTypes;
		};
	}

	/**
	 * The pre-value half of an assignment: walks the target's sub-expressions
	 * (root, dimensions, receiver, dynamic name) in PHP's evaluation order and
	 * captures everything applyWrite() needs into a PreparedAssignTarget. The
	 * caller processes the assigned value on PreparedAssignTarget::getScope()
	 * between the two calls.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function prepareTarget(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		Node\Stmt $stmt,
		Expr $var,
		Expr $assignedExpr,
		callable $nodeCallback,
		ExpressionContext $context,
		AssignTargetWalkMode $mode,
	): PreparedAssignTarget
	{
		// The raw target's node callback fires after the walk below composed and
		// stored the target's read result, with the scope captured at entry -
		// a synchronously invoked rule (the plain resolver, PHP < 8.1) then
		// answers its asks from the storage instead of re-walking on demand,
		// same as NodeScopeResolver::processExprNodeInternal().
		$prepared = $this->doPrepareTarget($nodeScopeResolver, $scope, $storage, $stmt, $var, $assignedExpr, $nodeCallback, $context, $mode);
		$nodeScopeResolver->callNodeCallback($nodeCallback, $var, $mode->enterExpressionAssign() ? $scope->enterExpressionAssign($var) : $scope, $storage);

		return $prepared;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function doPrepareTarget(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		Node\Stmt $stmt,
		Expr $var,
		Expr $assignedExpr,
		callable $nodeCallback,
		ExpressionContext $context,
		AssignTargetWalkMode $mode,
	): PreparedAssignTarget
	{
		$enterExpressionAssign = $mode->enterExpressionAssign();
		$targetReadResult = null;
		$targetChainResults = [];
		$beforeScope = $scope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$isAssignOp = $assignedExpr instanceof Expr\AssignOp && !$enterExpressionAssign;
		if ($var instanceof Variable) {
			$variableNameResult = null;
			if ($mode->producesTargetReadResult()) {
				// `$lvalue OP= ...` reads the old value of `$lvalue`; the write walk
				// processes a Variable target only as an assignment target, never as
				// a read. The read result is composed here without a walk - the
				// ??= read with isset() semantics (mirroring CoalesceHandler's
				// left-side processing, with the isset descriptor - bug-13623).
				if (!is_string($var->name)) {
					// `$$name OP= ...` evaluates the name before reading the old
					// value: walk it once here, the write flow consumes the result
					$variableNameResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
					$hasYield = $variableNameResult->hasYield();
					$throwPoints = $variableNameResult->getThrowPoints();
					$impurePoints = $variableNameResult->getImpurePoints();
					$isAlwaysTerminating = $variableNameResult->isAlwaysTerminating();
					$scope = $variableNameResult->getScope();
				}
				$readScope = $scope;
				if ($mode->issetSemanticsForRead()) {
					$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $var);
					$readScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $var);
				}
				$targetReadResult = $this->variableHandler->composeResult($nodeScopeResolver, $var, $variableNameResult, $storage, $readScope);
				if ($mode->issetSemanticsForRead()) {
					$targetChainResults[spl_object_id($var)] = $targetReadResult;
				}
			}

			return new PreparedAssignTarget(
				PreparedAssignTarget::KIND_VARIABLE,
				$var,
				$assignedExpr,
				$beforeScope,
				$scope,
				$enterExpressionAssign,
				$isAssignOp,
				$hasYield,
				$throwPoints,
				$impurePoints,
				$isAlwaysTerminating,
				targetReadResult: $targetReadResult,
				targetChainResults: $targetChainResults,
				variableNameResult: $variableNameResult,
			);
		}

		if ($var instanceof ArrayDimFetch) {
			$dimFetchStack = [];
			$originalVar = $var;
			$scopeBeforeTargetWalk = $scope;
			while ($var instanceof ArrayDimFetch) {
				$dimFetchStack[] = $var;
				$var = $var->var;
			}

			// 1. eval root expr
			// The root is read to obtain the container that receives the offset write, so a
			// property root must resolve to its readable type (not its writable one) even
			// though it sits on the left-hand side of the assignment.
			if ($enterExpressionAssign) {
				$scope = $scope->enterExpressionAssign($var, false);
			}
			$varResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $varResult->hasYield();
			$throwPoints = $varResult->getThrowPoints();
			$impurePoints = $varResult->getImpurePoints();
			$isAlwaysTerminating = $varResult->isAlwaysTerminating();
			$scope = $varResult->getScope();
			if ($enterExpressionAssign) {
				$scope = $scope->exitExpressionAssign($var);
			}

			// 1b. build the write chain (Set*OffsetValueTypeExpr nesting) after
			// the root walk, so a property base's holder and current types are
			// read from its stored result instead of pricing the unwalked fetch
			$assignedPropertyExpr = $assignedExpr;
			$chainVar = $originalVar;
			while ($chainVar instanceof ArrayDimFetch) {
				$varForSetOffsetValue = $chainVar->var;
				if ($varForSetOffsetValue instanceof PropertyFetch || $varForSetOffsetValue instanceof StaticPropertyFetch) {
					$varForSetOffsetValue = new TypeExpr($this->getOriginalPropertyType($nodeScopeResolver, $varForSetOffsetValue, $scope));
				}

				if (
					$chainVar === $originalVar
					&& $chainVar->dim !== null
					&& $scopeBeforeTargetWalk->hasExpressionType($chainVar)->yes()
				) {
					$assignedPropertyExpr = new SetExistingOffsetValueTypeExpr(
						$varForSetOffsetValue,
						$chainVar->dim,
						$assignedPropertyExpr,
					);
				} else {
					$assignedPropertyExpr = new SetOffsetValueTypeExpr(
						$varForSetOffsetValue,
						$chainVar->dim,
						$assignedPropertyExpr,
					);
				}
				$chainVar = $chainVar->var;
			}

			// 2. eval dimensions
			$offsetTypes = [];
			$offsetNativeTypes = [];
			$dimResults = [];
			$deferredDimFetchResults = [];
			$dimFetchStack = array_reverse($dimFetchStack);
			$lastDimKey = array_key_last($dimFetchStack);
			$previousLinkResult = $varResult;
			foreach ($dimFetchStack as $key => $dimFetch) {
				$dimExpr = $dimFetch->dim;
				$callbackScope = $scope;

				if ($dimExpr === null) {
					$offsetTypes[] = [null, $dimFetch];
					$offsetNativeTypes[] = [null, $dimFetch];
					$dimResults[$key] = null;
					$fabricatedResult = $this->expressionResultFactory->create(
						$scope,
						beforeScope: $scope,
						expr: $dimFetch,
						hasYield: false,
						isAlwaysTerminating: false,
						throwPoints: [],
						impurePoints: [],
						typeCallback: static fn (): Type => new NeverType(),
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					);
					$deferredDimFetchResults[] = [$dimFetch, $fabricatedResult];
					$previousLinkResult = $fabricatedResult;

				} else {
					if ($enterExpressionAssign) {
						$scope->enterExpressionAssign($dimExpr);
					}
					// process the dimension first, then consume its ExpressionResult
					// (single-pass inside-out) rather than reading it before processExprNode()
					$result = $nodeScopeResolver->processExprNode($stmt, $dimExpr, $scope, $storage, $nodeCallback, $context->enterDeep());
					$dimResults[$key] = $result;
					$offsetTypes[] = [$result->getType(), $dimFetch];
					$offsetNativeTypes[] = [$result->getNativeType(), $dimFetch];
					$hasYield = $hasYield || $result->hasYield();
					$throwPoints = array_merge($throwPoints, $result->getThrowPoints());

					$dimNodeResult = $result;
					$fabricatedResult = $this->expressionResultFactory->create(
						$scope,
						beforeScope: $scope,
						expr: $dimFetch,
						hasYield: false,
						isAlwaysTerminating: false,
						throwPoints: [],
						impurePoints: [],
						typeCallback: static function (bool $nativeTypesPromoted) use ($previousLinkResult, $dimNodeResult, $scope): Type {
							$s = $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope;

							return $previousLinkResult->getTypeOnScope($s, $s->nativeTypesPromoted)->getOffsetValueType($dimNodeResult->getTypeOnScope($s, $s->nativeTypesPromoted));
						},
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					);
					$deferredDimFetchResults[] = [$dimFetch, $fabricatedResult];
					$previousLinkResult = $fabricatedResult;
					$scope = $result->getScope();

					if ($enterExpressionAssign) {
						$scope = $scope->exitExpressionAssign($dimExpr);
					}
				}

				// The whole target's callback fires in prepareTarget() after the
				// walk; an intermediate link's fires here, after its dimension was
				// processed and its write-flavoured result stored, so callback-side
				// asks answer from the storage with the link's entry scope.
				if ($key === $lastDimKey) {
					continue;
				}

				$nodeScopeResolver->storeExpressionResult($storage, $dimFetch, $previousLinkResult);
				$nodeScopeResolver->callNodeCallback($nodeCallback, $dimFetch, $enterExpressionAssign ? $callbackScope->enterExpressionAssign($dimFetch) : $callbackScope, $storage);
			}

			if ($mode->issetSemanticsForRead()) {
				// `$lvalue ??= ...` reads the chain with isset() semantics. The root
				// and dimensions were just walked, so each chain link's read is
				// composed from their results - no re-walk. The reads carry the isset
				// descriptor (bug-13623) and are stored, which is what parked rule
				// asks observe; the write-flavoured results below then replace them
				// in storage, exactly as before.
				$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $originalVar);
				$readScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $originalVar);
				$levelReadResult = $varResult;
				foreach ($dimFetchStack as $key => $dimFetch) {
					$levelReadResult = $this->arrayDimFetchHandler->composeResult($nodeScopeResolver, $stmt, $dimFetch, $dimResults[$key], $levelReadResult, $storage, $context, $readScope);
					$nodeScopeResolver->storeExpressionResult($storage, $dimFetch, $levelReadResult);
					$targetChainResults[spl_object_id($dimFetch)] = $levelReadResult;
					if ($dimFetch->dim === null || $dimResults[$key] === null) {
						continue;
					}

					$targetChainResults[spl_object_id($dimFetch->dim)] = $dimResults[$key];
				}
				$targetReadResult = $levelReadResult;
				// the root (and, when it is itself a fetch chain, its links) was
				// stored by its own walk above
				$this->defaultNarrowingHelper->captureChainResults($var, $storage, $targetChainResults);
			} elseif ($mode->producesTargetReadResult()) {
				// `$lvalue OP= ...`: the value the target reads is the write-flavoured
				// result of the whole chain, fabricated above
				[, $targetReadResult] = $deferredDimFetchResults[count($deferredDimFetchResults) - 1];
			}
			foreach ($deferredDimFetchResults as [$deferredDimFetch, $deferredResult]) {
				$nodeScopeResolver->storeExpressionResult($storage, $deferredDimFetch, $deferredResult);
			}
			// the chain link the write's ArrayAccess::offsetSet would be invoked on:
			// the second-outermost link, or the root for a single-dimension target
			$offsetSetTargetResult = count($deferredDimFetchResults) >= 2
				? $deferredDimFetchResults[count($deferredDimFetchResults) - 2][1]
				: $varResult;

			return new PreparedAssignTarget(
				PreparedAssignTarget::KIND_ARRAY_DIM_FETCH,
				$originalVar,
				$assignedExpr,
				$beforeScope,
				$scope,
				$enterExpressionAssign,
				$isAssignOp,
				$hasYield,
				$throwPoints,
				$impurePoints,
				$isAlwaysTerminating,
				rootVar: $var,
				varResult: $varResult,
				dimFetchStack: $dimFetchStack,
				assignedPropertyExpr: $assignedPropertyExpr,
				offsetTypes: $offsetTypes,
				offsetNativeTypes: $offsetNativeTypes,
				offsetSetTargetResult: $offsetSetTargetResult,
				targetReadResult: $targetReadResult,
				targetChainResults: $targetChainResults,
			);
		}

		if ($var instanceof PropertyFetch) {
			$scopeBeforeVar = $scope;
			$objectResult = $nodeScopeResolver->processExprNode($stmt, $var->var, $scope, $storage, $nodeCallback, $context);
			$hasYield = $objectResult->hasYield();
			$throwPoints = $objectResult->getThrowPoints();
			$impurePoints = $objectResult->getImpurePoints();
			$isAlwaysTerminating = $objectResult->isAlwaysTerminating();
			$scope = $objectResult->getScope();

			$propertyName = null;
			$propertyNameResult = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $hasYield || $propertyNameResult->hasYield();
				$throwPoints = array_merge($throwPoints, $propertyNameResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $propertyNameResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $propertyNameResult->isAlwaysTerminating();
				$scope = $propertyNameResult->getScope();
			}

			$scopeBeforeAssignEval = $scope;
			if ($mode->issetSemanticsForRead()) {
				// `$lvalue ??= ...` reads the property with isset() semantics: the
				// read is composed from the just-walked receiver and name results -
				// no re-walk - and carries the isset descriptor (bug-13623). Stored
				// so parked rule asks observe the read flavour, exactly as they
				// observed the former pre-read's store.
				$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $var);
				$readScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $var);
				$targetReadResult = $this->propertyFetchHandler->composeResult($nodeScopeResolver, $var, $objectResult, $propertyNameResult, $scopeBeforeVar, $readScope);
				$nodeScopeResolver->storeExpressionResult($storage, $var, $targetReadResult);
				$this->defaultNarrowingHelper->captureChainResults($var, $storage, $targetChainResults);
			}
			// The raw target fetch was emitted to node callbacks at the top of
			// prepareTarget() but the assign flow never processes it as a
			// read. Compose and store it once here from the receiver's and
			// name's results, so askers parked on it (DependencyResolver,
			// property rules) resume with its pre-assign type.
			$parkedReadResult = $this->propertyFetchHandler->composeResult($nodeScopeResolver, $var, $objectResult, $propertyNameResult, $scopeBeforeVar, $scopeBeforeAssignEval);
			$nodeScopeResolver->storeExpressionResult($storage, $var, $parkedReadResult);
			if ($mode->producesTargetReadResult() && !$mode->issetSemanticsForRead()) {
				$targetReadResult = $parkedReadResult;
			}

			return new PreparedAssignTarget(
				PreparedAssignTarget::KIND_PROPERTY_FETCH,
				$var,
				$assignedExpr,
				$beforeScope,
				$scope,
				$enterExpressionAssign,
				$isAssignOp,
				$hasYield,
				$throwPoints,
				$impurePoints,
				$isAlwaysTerminating,
				objectResult: $objectResult,
				propertyName: $propertyName,
				targetReadResult: $targetReadResult,
				targetChainResults: $targetChainResults,
			);
		}

		if ($var instanceof Expr\StaticPropertyFetch) {
			$classResult = null;
			if ($var->class instanceof Node\Name) {
				$propertyHolderType = $scope->resolveTypeByName($var->class);
			} else {
				$classResult = $nodeScopeResolver->processExprNode($stmt, $var->class, $scope, $storage, $nodeCallback, $context);
				$propertyHolderType = $classResult->getType();
			}

			$propertyName = null;
			$propertyNameResult = null;
			if ($var->name instanceof Node\Identifier) {
				$propertyName = $var->name->name;
			} else {
				$propertyNameResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $propertyNameResult->hasYield();
				$throwPoints = $propertyNameResult->getThrowPoints();
				$impurePoints = $propertyNameResult->getImpurePoints();
				$isAlwaysTerminating = $propertyNameResult->isAlwaysTerminating();
				$scope = $propertyNameResult->getScope();
			}

			$scopeBeforeAssignEval = $scope;
			if ($mode->issetSemanticsForRead()) {
				// Same as the PropertyFetch branch above: the ??= read is composed
				// from the just-walked class/name results on the isset-semantics
				// scope - no re-walk.
				$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $var);
				$readScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $var);
				$targetReadResult = $this->staticPropertyFetchHandler->composeResult($var, $classResult, $propertyNameResult, $readScope);
				$nodeScopeResolver->storeExpressionResult($storage, $var, $targetReadResult);
				$this->defaultNarrowingHelper->captureChainResults($var, $storage, $targetChainResults);
			}
			// Same as the PropertyFetch branch above: the emitted target fetch
			// needs a stored result for parked askers.
			$parkedReadResult = $this->staticPropertyFetchHandler->composeResult($var, $classResult, $propertyNameResult, $scopeBeforeAssignEval);
			$nodeScopeResolver->storeExpressionResult($storage, $var, $parkedReadResult);
			if ($mode->producesTargetReadResult() && !$mode->issetSemanticsForRead()) {
				$targetReadResult = $parkedReadResult;
			}

			return new PreparedAssignTarget(
				PreparedAssignTarget::KIND_STATIC_PROPERTY_FETCH,
				$var,
				$assignedExpr,
				$beforeScope,
				$scope,
				$enterExpressionAssign,
				$isAssignOp,
				$hasYield,
				$throwPoints,
				$impurePoints,
				$isAlwaysTerminating,
				propertyName: $propertyName,
				propertyHolderType: $propertyHolderType,
				targetReadResult: $targetReadResult,
				targetChainResults: $targetChainResults,
			);
		}

		if ($var instanceof List_) {
			return new PreparedAssignTarget(
				PreparedAssignTarget::KIND_LIST,
				$var,
				$assignedExpr,
				$beforeScope,
				$scope,
				$enterExpressionAssign,
				$isAssignOp,
				$hasYield,
				$throwPoints,
				$impurePoints,
				$isAlwaysTerminating,
			);
		}

		if ($var instanceof ExistingArrayDimFetch) {
			$originalVar = $var;
			$dimFetchStack = [];
			$assignedPropertyExpr = $assignedExpr;
			while ($var instanceof ExistingArrayDimFetch) {
				$varForSetOffsetValue = $var->getVar();
				if ($varForSetOffsetValue instanceof PropertyFetch || $varForSetOffsetValue instanceof StaticPropertyFetch) {
					$varForSetOffsetValue = new TypeExpr($this->getOriginalPropertyType($nodeScopeResolver, $varForSetOffsetValue, $scope));
				}
				$assignedPropertyExpr = new SetExistingOffsetValueTypeExpr(
					$varForSetOffsetValue,
					$var->getDim(),
					$assignedPropertyExpr,
				);
				$dimFetchStack[] = $var;
				$var = $var->getVar();
			}

			// the chain links reference the original, already-processed AST nodes
			// (see the Unset_ handling) - read their stored results, no walk
			$varResult = $nodeScopeResolver->readStoredResult($var, $storage);

			$offsetTypes = [];
			$offsetNativeTypes = [];
			foreach (array_reverse($dimFetchStack) as $dimFetch) {
				$dimResult = $nodeScopeResolver->readStoredResult($dimFetch->getDim(), $storage);
				$offsetTypes[] = [$dimResult->getType(), $dimFetch];
				$offsetNativeTypes[] = [$dimResult->getNativeType(), $dimFetch];
			}

			return new PreparedAssignTarget(
				PreparedAssignTarget::KIND_EXISTING_ARRAY_DIM_FETCH,
				$originalVar,
				$assignedExpr,
				$beforeScope,
				$scope,
				$enterExpressionAssign,
				$isAssignOp,
				$hasYield,
				$throwPoints,
				$impurePoints,
				$isAlwaysTerminating,
				rootVar: $var,
				varResult: $varResult,
				assignedPropertyExpr: $assignedPropertyExpr,
				existingOffsetTypes: $offsetTypes,
				existingOffsetNativeTypes: $offsetNativeTypes,
			);
		}

		$varResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context);
		$hasYield = $varResult->hasYield();
		$throwPoints = array_merge($throwPoints, $varResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
		$isAlwaysTerminating = $varResult->isAlwaysTerminating();
		$scope = $varResult->getScope();

		if ($mode->producesTargetReadResult()) {
			// a synthetic op=/??= target (e.g. InvalidBinaryOperationRule's
			// TypeExpr-operand clone priced on demand): the walk above already
			// priced the target as a read - its result is the read
			$targetReadResult = $varResult;
			if ($mode->issetSemanticsForRead()) {
				$targetChainResults[spl_object_id($var)] = $varResult;
			}
		}

		return new PreparedAssignTarget(
			PreparedAssignTarget::KIND_FALLBACK,
			$var,
			$assignedExpr,
			$beforeScope,
			$scope,
			$enterExpressionAssign,
			$isAssignOp,
			$hasYield,
			$throwPoints,
			$impurePoints,
			$isAlwaysTerminating,
			targetReadResult: $targetReadResult,
			targetChainResults: $targetChainResults,
		);
	}

	/**
	 * The post-value half of an assignment: performs the write and its
	 * bookkeeping (narrowing, conditional expressions, node callbacks) for a
	 * target walked by prepareTarget(), consuming the caller-processed value
	 * result. $valueResult carries the value evaluation's scope and points;
	 * $assignedValueResult is the result standing for the assigned expression
	 * itself (the value to write) - null lets the reads fall back to stored
	 * results or on-demand pricing.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function applyWrite(
		NodeScopeResolver $nodeScopeResolver,
		PreparedAssignTarget $target,
		ExpressionResult $valueResult,
		?ExpressionResult $assignedValueResult,
		Node\Stmt $stmt,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
	): ExpressionResult
	{
		$kind = $target->getKind();
		$var = $target->getVar();
		$assignedExpr = $target->getAssignedExpr();
		$beforeScope = $target->getBeforeScope();
		$scope = $target->getScope();
		$enterExpressionAssign = $target->enterExpressionAssign();
		$isAssignOp = $target->isAssignOp();
		$hasYield = $target->hasYield();
		$throwPoints = $target->getThrowPoints();
		$impurePoints = $target->getImpurePoints();
		$isAlwaysTerminating = $target->isAlwaysTerminating();
		if ($kind === PreparedAssignTarget::KIND_VARIABLE) {
			if (!$var instanceof Variable) {
				throw new ShouldNotHappenException();
			}
			$result = $valueResult;
			$hasYield = $result->hasYield();
			$throwPoints = $result->getThrowPoints();
			$impurePoints = $result->getImpurePoints();
			$isAlwaysTerminating = $result->isAlwaysTerminating();
			$scopeBeforeAssignEval = $scope;
			$scope = $result->getScope();
			if (is_string($var->name)) {
				if (in_array($var->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
					$impurePoints[] = new ImpurePoint($scopeBeforeAssignEval, $var, 'superglobal', 'assign to superglobal variable', true);
				}
				$assignedExpr = $this->unwrapAssign($assignedExpr);
				// the caller-passed value result; a nested assign chain's value is the
				// innermost assigned expression, whose result comes from the storage
				// the walk just wrote into (the one read this method cannot avoid)
				$storedAssignedExprResult = $assignedExpr === $target->getAssignedExpr()
					? $assignedValueResult ?? $storage->findExpressionResult($assignedExpr)
					: $storage->findExpressionResult($assignedExpr);
				$assignedValueResult = $storedAssignedExprResult;
				$type = $this->readAssignedValueType($nodeScopeResolver, $storedAssignedExprResult, $assignedExpr, $scopeBeforeAssignEval);

				$conditionalExpressions = [];
				if ($assignedExpr instanceof Ternary) {
					// the walk already evaluated the arms on the cond-filtered
					// scopes - read the captured results instead of re-walking
					$capturedTernary = $this->ternaryHandler->getCapturedResults($assignedExpr);
					if ($capturedTernary !== null) {
						[$ternaryCondResult, $ternaryIfResult, $ternaryElseResult] = $capturedTernary;
						$condScope = $ternaryCondResult->getScope();
						$truthySpecifiedTypes = $ternaryCondResult->getSpecifiedTypesForScope($condScope, TypeSpecifierContext::createTruthy());
						$falseySpecifiedTypes = $ternaryCondResult->getSpecifiedTypesForScope($condScope, TypeSpecifierContext::createFalsey());
						$truthyType = $ternaryIfResult->getType();
						$falseyType = $ternaryElseResult->getType();
					} else {
						$if = $assignedExpr->if;
						if ($if === null) {
							$if = $assignedExpr->cond;
						}
						$condScope = $nodeScopeResolver->processExprNode($stmt, $assignedExpr->cond, $scope, $storage->duplicate(), new NoopNodeCallback(), ExpressionContext::createDeep())->getScope();
						$truthySpecifiedTypes = $this->defaultNarrowingHelper->specifyTypesForNode($condScope, $assignedExpr->cond, TypeSpecifierContext::createTruthy());
						$falseySpecifiedTypes = $this->defaultNarrowingHelper->specifyTypesForNode($condScope, $assignedExpr->cond, TypeSpecifierContext::createFalsey());
						$truthyScope = $condScope->applySpecifiedTypes($truthySpecifiedTypes);
						$falsyScope = $condScope->applySpecifiedTypes($falseySpecifiedTypes);
						// the arms of this unwalked ternary are re-priced on the
						// narrowed cond scopes - scope state answers plain reads,
						// anything else is priced on demand
						$truthyType = $nodeScopeResolver->findScopeStateType($if, $truthyScope)
							?? $nodeScopeResolver->processSyntheticOnDemand($if, $truthyScope)->getTypeOnScope($truthyScope, $truthyScope->nativeTypesPromoted);
						$falseyType = $nodeScopeResolver->findScopeStateType($assignedExpr->else, $falsyScope)
							?? $nodeScopeResolver->processSyntheticOnDemand($assignedExpr->else, $falsyScope)->getTypeOnScope($falsyScope, $falsyScope->nativeTypesPromoted);
					}

					if (
						$truthyType->isSuperTypeOf($falseyType)->no()
						&& $falseyType->isSuperTypeOf($truthyType)->no()
					) {
						$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $condScope, $storage, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
						$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $condScope, $storage, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
						$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $condScope, $storage, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
						$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $condScope, $storage, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
					}
				}

				if ($assignedExpr instanceof Match_) {
					$conditionalExpressions = $this->mergeConditionalExpressions(
						$conditionalExpressions,
						$this->processMatchForConditionalExpressionsAfterAssign($nodeScopeResolver, $scopeBeforeAssignEval, $storage, $var->name, $assignedExpr),
					);
				}

				$assignedArgResult = $this->identicalNarrowingHelper->captureFirstArgResult($assignedExpr, $storage);

				$truthyType = TypeCombinator::removeFalsey($type);
				// Value comparison, not identity: remove() happens to hand back the very same
				// instance when it removes nothing, but that is not part of its contract — the
				// falsey loop below already compares with equals(). The identity check is only
				// a fast path (equals() has no such shortcut, and no-op removal is the common
				// case here).
				if ($truthyType !== $type && !$truthyType->equals($type)) {
					$truthySpecifiedTypes = $storedAssignedExprResult !== null
						? $storedAssignedExprResult->getSpecifiedTypesForScope($scope, TypeSpecifierContext::createTruthy())
						: $this->defaultNarrowingHelper->specifyTypesForNode($scope, $assignedExpr, TypeSpecifierContext::createTruthy());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $truthySpecifiedTypes, $truthyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);

					$falseyType = TypeCombinator::intersect($type, StaticTypeFactory::falsey());
					$falseySpecifiedTypes = $storedAssignedExprResult !== null
						? $storedAssignedExprResult->getSpecifiedTypesForScope($scope, TypeSpecifierContext::createFalsey())
						: $this->defaultNarrowingHelper->specifyTypesForNode($scope, $assignedExpr, TypeSpecifierContext::createFalsey());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $falseySpecifiedTypes, $falseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
				}

				foreach ([null, false, 0, 0.0, '', '0', []] as $falseyScalar) {
					$falseyType = ConstantTypeHelper::getTypeFromValue($falseyScalar);
					$withoutFalseyType = TypeCombinator::remove($type, $falseyType);
					if (
						$withoutFalseyType->equals($type)
						|| $withoutFalseyType->equals($truthyType)
					) {
						continue;
					}

					if ($falseyScalar === null) {
						$astNode = new ConstFetch(new Name('null'));
					} elseif ($falseyScalar === false) {
						$astNode = new ConstFetch(new Name('false'));
					} elseif ($falseyScalar === 0) {
						$astNode = new Node\Scalar\Int_($falseyScalar);
					} elseif ($falseyScalar === 0.0) {
						$astNode = new Node\Scalar\Float_($falseyScalar);
					} elseif (in_array($falseyScalar, ['', '0'], true)) {
						$astNode = new Node\Scalar\String_($falseyScalar);
					} elseif ($falseyScalar === []) {
						$astNode = new Node\Expr\Array_($falseyScalar);
					}

					// the identical verdict of "assigned expr vs the sentinel":
					// the loop guarantees the sentinel is a possible value, so
					// only always-the-sentinel is decided
					$identicalTypeCallback = static fn (): Type => $type->equals($falseyType)
						? new ConstantBooleanType(true)
						: new BooleanType();

					$notIdenticalSpecifiedTypes = $storedAssignedExprResult !== null
						? $this->identicalNarrowingHelper->specifyIdenticalAgainstType($assignedExpr, $storedAssignedExprResult, $astNode, $falseyType, TypeSpecifierContext::createFalse(), $scope, $assignedArgResult, $identicalTypeCallback)
						: null;
					$notIdenticalSpecifiedTypes ??= $this->defaultNarrowingHelper->specifyTypesForNode($scope, new Expr\BinaryOp\NotIdentical($assignedExpr, $astNode), TypeSpecifierContext::createTrue());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $notIdenticalSpecifiedTypes, $withoutFalseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $notIdenticalSpecifiedTypes, $withoutFalseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);

					$identicalSpecifiedTypes = $storedAssignedExprResult !== null
						? $this->identicalNarrowingHelper->specifyIdenticalAgainstType($assignedExpr, $storedAssignedExprResult, $astNode, $falseyType, TypeSpecifierContext::createTrue(), $scope, $assignedArgResult, $identicalTypeCallback)
						: null;
					$identicalSpecifiedTypes ??= $this->defaultNarrowingHelper->specifyTypesForNode($scope, new Expr\BinaryOp\Identical($assignedExpr, $astNode), TypeSpecifierContext::createTrue());
					$conditionalExpressions = $this->processSureTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $identicalSpecifiedTypes, $falseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
					$conditionalExpressions = $this->processSureNotTypesForConditionalExpressionsAfterAssign($nodeScopeResolver, $scope, $storage, $var->name, $conditionalExpressions, $identicalSpecifiedTypes, $falseyType, $impurePoints, $assignedExpr, $storedAssignedExprResult);
				}

				$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedExpr), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignVariable($var->name, $type, $this->readAssignedValueType($nodeScopeResolver, $storedAssignedExprResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()), TrinaryLogic::createYes());
				foreach ($conditionalExpressions as $exprString => $holders) {
					$scope = $scope->addConditionalExpressions((string) $exprString, $holders);
				}

				if ($assignedExpr instanceof Expr\Array_) {
					$scope = $this->processArrayByRefItems($nodeScopeResolver, $scope, $storage, $var->name, $assignedExpr, new Variable($var->name));
				}
			} elseif ($target->getVariableNameResult() === null) {
				// a plain assignment does not read the target, so the dynamic name
				// is walked here; read-modify-write targets walked it in
				// prepareTarget() and already carry its state
				$nameExprResult = $nodeScopeResolver->processExprNode($stmt, $var->name, $scope, $storage, $nodeCallback, $context);
				$hasYield = $hasYield || $nameExprResult->hasYield();
				$throwPoints = array_merge($throwPoints, $nameExprResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $nameExprResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $nameExprResult->isAlwaysTerminating();
				$scope = $nameExprResult->getScope();
			}
		} elseif ($kind === PreparedAssignTarget::KIND_ARRAY_DIM_FETCH) {
			if (!$var instanceof ArrayDimFetch) {
				throw new ShouldNotHappenException();
			}
			$var = $target->getRootVar();
			$varResult = $target->getVarResult();
			$dimFetchStack = $target->getDimFetchStack();
			$assignedPropertyExpr = $target->getAssignedPropertyExpr();
			$offsetTypes = $target->getOffsetTypes();
			$offsetNativeTypes = $target->getOffsetNativeTypes();
			// 3. eval assigned expr first, then read the assigned value on the pre-eval
			// scope - so the read consumes the now-stored result of $assignedExpr (and
			// of its operands) instead of pricing unprocessed nodes (mirrors the
			// Variable branch above). The ??= left side's optional array{} branch is
			// preserved by the coalesce typeCallback carrying the isset descriptor, not
			// by reading a stale resolvedTypes cache (bug-13623).
			$scopeBeforeAssignEval = $scope;
			$result = $valueResult;
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			// read from the storage the walk just wrote into - the scope's storage
			// stack misses it on loop-convergence passes (the temp storage is never
			// pushed), which fell back to a full on-demand re-walk of the assigned
			// expression for both flavours
			$storedValueResult = $assignedValueResult ?? $storage->findExpressionResult($assignedExpr);
			$nativeScopeBeforeAssignEval = $scopeBeforeAssignEval->doNotTreatPhpDocTypesAsCertain();
			$valueToWrite = $this->readAssignedValueType($nodeScopeResolver, $storedValueResult, $assignedExpr, $scopeBeforeAssignEval);
			$nativeValueToWrite = $this->readAssignedValueType($nodeScopeResolver, $storedValueResult, $assignedExpr, $nativeScopeBeforeAssignEval);

			[$varType, $varNativeType] = $this->resolveContainerTypesAfterAssignedExprEval($nodeScopeResolver, $var, $varResult, $scope, $scopeBeforeAssignEval, $storage);

			// 4. compose types
			$isImplicitArrayCreation = $this->isImplicitArrayCreation($dimFetchStack, $scope);
			if ($isImplicitArrayCreation->yes()) {
				$varType = new ConstantArrayType([], []);
				$varNativeType = new ConstantArrayType([], []);
			}
			$offsetValueType = $varType;
			$offsetNativeValueType = $varNativeType;

			[$valueToWrite, $additionalExpressions] = $this->produceArrayDimFetchAssignValueToWrite($nodeScopeResolver, $dimFetchStack, $offsetTypes, $offsetValueType, $valueToWrite, $scope, $storage);

			if (!$offsetValueType->equals($offsetNativeValueType) || !$valueToWrite->equals($nativeValueToWrite)) {
				[$nativeValueToWrite, $additionalNativeExpressions] = $this->produceArrayDimFetchAssignValueToWrite($nodeScopeResolver, $dimFetchStack, $offsetNativeTypes, $offsetNativeValueType, $nativeValueToWrite, $scope, $storage);
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

					[$nativeValueToWrite] = $this->produceArrayDimFetchAssignValueToWrite($nodeScopeResolver, $dimFetchStack, $offsetNativeTypes, $offsetNativeValueType, $nativeValueToWrite, $scope, $storage);
					$rewritten = true;
					break;
				}

				if (!$rewritten) {
					$nativeValueToWrite = $valueToWrite;
				}
			}

			if ($varType->isArray()->yes() || !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->yes()) {
				if ($var instanceof Variable && is_string($var->name)) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, new TypeExpr($valueToWrite)), $scopeBeforeAssignEval, $storage);
					$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite, TrinaryLogic::createYes());
				} else {
					if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
						$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
						if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
							// the chain root's receiver was walked by prepareTarget()
							$scope = $scope->assignInitializedProperty($nodeScopeResolver->readStoredResult($var->var, $storage)->getTypeOnScope($scope, $scope->nativeTypesPromoted), $var->name->toString());
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
					$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scopeBeforeAssignEval, $storage);
				} elseif ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
					if ($var instanceof PropertyFetch && $var->name instanceof Node\Identifier && !$isAssignOp) {
						// the chain root's receiver was walked by prepareTarget()
						$scope = $scope->assignInitializedProperty($nodeScopeResolver->readStoredResult($var->var, $storage)->getTypeOnScope($scope, $scope->nativeTypesPromoted), $var->name->toString());
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

			// the second-outermost chain link's result (for a single-dimension
			// target: the root), threaded from the walk
			$setVarType = $target->getOffsetSetTargetResult()->getTypeOnScope($scope, $scope->nativeTypesPromoted);
			if (
				!$setVarType instanceof ErrorType
				&& !$setVarType->isArray()->yes()
				&& !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($setVarType)->no()
			) {
				$throwPoints = array_merge($throwPoints, $this->methodThrowPointHelper->getThrowPointsForCallOnType(
					$scope,
					$context,
					$setVarType,
					new MethodCall(new TypeExpr($setVarType), 'offsetSet'),
				));
			}
		} elseif ($kind === PreparedAssignTarget::KIND_PROPERTY_FETCH) {
			if (!$var instanceof PropertyFetch) {
				throw new ShouldNotHappenException();
			}
			$objectResult = $target->getObjectResult();
			$propertyName = $target->getPropertyName();
			$scopeBeforeAssignEval = $scope;
			$result = $valueResult;
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			if ($var->name instanceof Expr && $this->phpVersion->supportsPropertyHooks()) {
				$throwPoints[] = InternalThrowPoint::createImplicit($scope, $var);
			}

			$propertyHolderType = $objectResult->getType();
			if ($propertyName !== null && $propertyHolderType->hasInstanceProperty($propertyName)->yes()) {
				$propertyReflection = $propertyHolderType->getInstanceProperty($propertyName, $scope);
				$assignedExprType = $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
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
							$scope = $scope->assignExpression($var, $assignedExprType, $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()));
						} else {
							$scope = $scope->assignExpression(
								$var,
								TypeCombinator::intersect($assignedExprType->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
								TypeCombinator::intersect($this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain())->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
							);
						}
					} else {
						$scope = $scope->assignExpression($var, $assignedExprType, $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()));
					}
				}
				$declaringClass = $propertyReflection->getDeclaringClass();
				if ($declaringClass->hasNativeProperty($propertyName)) {
					$nativeProperty = $declaringClass->getNativeProperty($propertyName);
					$propertyNativeType = $nativeProperty->getNativeType();

					$assignedTypeIsCompatible = $propertyNativeType->isSuperTypeOf($assignedExprType)->yes();
					if (!$assignedTypeIsCompatible && !$assignedExprType instanceof MixedType) {
						foreach (TypeUtils::flattenTypes($assignedExprType->toCoercedArgumentType(true)) as $type) {
							$accepts = $propertyNativeType->accepts($type, true);
							if ($accepts->yes()) {
								$assignedTypeIsCompatible = true;
								continue;
							}
							$assignedTypeIsCompatible = false;
							break;
						}
					}

					if (
						!$assignedTypeIsCompatible
					) {
						$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(TypeError::class), $assignedExpr, false);
					}
					if ($this->phpVersion->supportsPropertyHooks()) {
						$throwPoints = array_merge($throwPoints, $this->propertyHookThrowPointsResolver->getThrowPointsFromPropertyHook($scope, $var, $nativeProperty, 'set'));
					}
					if ($enterExpressionAssign) {
						$scope = $scope->assignInitializedProperty($propertyHolderType, $propertyName);
					}
				}
			} else {
				// fallback
				$assignedExprType = $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignExpression($var, $assignedExprType, $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()));
				// simulate dynamic property assign by __set to get throw points
				if (!$propertyHolderType->hasMethod('__set')->no()) {
					$throwPoints = array_merge($throwPoints, $this->methodThrowPointHelper->getThrowPointsForCallOnType(
						$scope,
						$context,
						$propertyHolderType,
						new MethodCall($var->var, '__set'),
					));
				}
			}

		} elseif ($kind === PreparedAssignTarget::KIND_STATIC_PROPERTY_FETCH) {
			if (!$var instanceof Expr\StaticPropertyFetch) {
				throw new ShouldNotHappenException();
			}
			$propertyHolderType = $target->getPropertyHolderType();
			$propertyName = $target->getPropertyName();
			$scopeBeforeAssignEval = $scope;
			$result = $valueResult;
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();

			if ($propertyName !== null) {
				$propertyReflection = $scope->getStaticPropertyReflection($propertyHolderType, $propertyName);
				$assignedExprType = $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
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
							$scope = $scope->assignExpression($var, $assignedExprType, $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()));
						} else {
							$scope = $scope->assignExpression(
								$var,
								TypeCombinator::intersect($assignedExprType->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
								TypeCombinator::intersect($this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain())->toCoercedArgumentType($scope->isDeclareStrictTypes()), $propertyNativeType),
							);
						}
					} else {
						$scope = $scope->assignExpression($var, $assignedExprType, $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()));
					}
				}
			} else {
				// fallback
				$assignedExprType = $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope);
				$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedExpr, $isAssignOp), $scopeBeforeAssignEval, $storage);
				$scope = $scope->assignExpression($var, $assignedExprType, $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain()));
			}
		} elseif ($kind === PreparedAssignTarget::KIND_LIST) {
			if (!$var instanceof List_) {
				throw new ShouldNotHappenException();
			}
			$result = $valueResult;
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
				$itemScope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($itemScope, $arrayItem->value);
				$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $itemScope, $storage);
				$keyResult = null;
				if ($arrayItem->key !== null) {
					$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $itemScope, $storage, $nodeCallback, $context->enterDeep());
					$hasYield = $hasYield || $keyResult->hasYield();
					$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
					$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
					$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
					$scope = $keyResult->getScope();
				}

				if ($keyResult !== null) {
					$dimType = $keyResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
				} else {
					$dimType = new ConstantIntegerType($i);
				}
				$getOffsetValueTypeExpr = new TypeExpr($this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope)->getOffsetValueType($dimType));
				// store the fabricated result so narrowing walks over the item value
				// compose from it instead of falling back to on-demand pricing
				$itemValueResult = $this->virtualExprResultHelper->createTypeExprResult($scope, $getOffsetValueTypeExpr);
				$nodeScopeResolver->storeExpressionResult($storage, $getOffsetValueTypeExpr, $itemValueResult);
				$itemTarget = $this->prepareTarget(
					$nodeScopeResolver,
					$scope,
					$storage,
					$stmt,
					$arrayItem->value,
					$getOffsetValueTypeExpr,
					$nodeCallback,
					$context,
					$enterExpressionAssign ? AssignTargetWalkMode::assign() : AssignTargetWalkMode::virtualAssign(),
				);
				$result = $this->applyWrite(
					$nodeScopeResolver,
					$itemTarget,
					$this->expressionResultFactory->create(
						$itemTarget->getScope(),
						beforeScope: $itemTarget->getScope(),
						expr: $getOffsetValueTypeExpr,
						hasYield: false,
						isAlwaysTerminating: false,
						throwPoints: [],
						impurePoints: [],
						typeCallback: static fn () => new MixedType(),
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					),
					$itemValueResult,
					$stmt,
					$storage,
					$nodeCallback,
					$context,
				);
				$scope = $result->getScope();
				$hasYield = $hasYield || $result->hasYield();
				$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			}
		} elseif ($kind === PreparedAssignTarget::KIND_EXISTING_ARRAY_DIM_FETCH) {
			$var = $target->getRootVar();
			$varResult = $target->getVarResult();
			$assignedPropertyExpr = $target->getAssignedPropertyExpr();
			$offsetTypes = $target->getExistingOffsetTypes();
			$offsetNativeTypes = $target->getExistingOffsetNativeTypes();
			$valueToWrite = $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope);
			$nativeValueToWrite = $this->readAssignedValueType($nodeScopeResolver, $assignedValueResult, $assignedExpr, $scope->doNotTreatPhpDocTypesAsCertain());
			[$varType, $varNativeType] = $this->resolveContainerTypesAfterAssignedExprEval($nodeScopeResolver, $var, $varResult, $scope, null, $storage);

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
				$nodeScopeResolver->callNodeCallback($nodeCallback, new VariableAssignNode($var, $assignedPropertyExpr), $scope, $storage);
				$scope = $scope->assignVariable($var->name, $valueToWrite, $nativeValueToWrite, TrinaryLogic::createYes());
			} else {
				if ($var instanceof PropertyFetch || $var instanceof StaticPropertyFetch) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new PropertyAssignNode($var, $assignedPropertyExpr, $isAssignOp), $scope, $storage);
				}
				$scope = $scope->assignExpression(
					$var,
					$valueToWrite,
					$nativeValueToWrite,
				);
			}
		} else {
			$result = $valueResult;
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}

		// stored where prepareTarget/applyWrite are called
		return $this->expressionResultFactory->create(
			$scope,
			$beforeScope,
			$var,
			$hasYield,
			$isAlwaysTerminating,
			$throwPoints,
			$impurePoints,
			typeCallback: static fn () => new MixedType(),
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
		);
	}

	private function createArrayDimFetchConditionalExpressionHolder(
		Expr\Variable $keyVar,
		Expr $arrayArg,
		Type $narrowedKeyType,
		Type $dimFetchType,
	): SpecifiedTypes
	{
		$dimFetch = new ArrayDimFetch($arrayArg, $keyVar);
		$dimFetchString = $this->exprPrinter->printExpr($dimFetch);
		$keyExprString = $this->exprPrinter->printExpr($keyVar);

		$holder = new ConditionalExpressionHolder(
			[$keyExprString => ExpressionTypeHolder::createYes($keyVar, $narrowedKeyType)],
			ExpressionTypeHolder::createYes($dimFetch, $dimFetchType),
		);

		return (new SpecifiedTypes([], []))->setNewConditionalExpressionHolders([
			$dimFetchString => [$holder->getKey() => $holder],
		]);
	}

	/**
	 * The assigned value's type at the given scope, read off the threaded result
	 * when available. No threaded result means a virtual assign: the value is a
	 * synthetic node (TypeExpr, or a composed dim fetch enterForeach() tracks in
	 * scope state).
	 */
	private function readAssignedValueType(NodeScopeResolver $nodeScopeResolver, ?ExpressionResult $assignedValueResult, Expr $assignedExpr, MutatingScope $scope): Type
	{
		if ($assignedValueResult !== null) {
			return $assignedValueResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		}

		return $nodeScopeResolver->findScopeStateType($assignedExpr, $scope)
			?? $nodeScopeResolver->processSyntheticOnDemand($assignedExpr, $scope)->getTypeOnScope($scope, $scope->nativeTypesPromoted);
	}

	/**
	 * The container's (phpdoc, native) type pair AFTER the assigned expression
	 * ran. The target's pre-eval walk result is stale when the assigned
	 * expression changed the container ($arr[2] = f($arr = [...]), an impure
	 * call invalidating the fetched property): a variable root and a
	 * still-tracked fetch read the post-eval state, a fetch the assigned
	 * expression invalidated (tracked before the eval, untracked after) is
	 * re-priced on the post-eval scope, and an untracked-throughout root keeps
	 * the walk-position type - nothing the assigned expression did could have
	 * changed what it reads.
	 *
	 * @return array{Type, Type}
	 */
	private function resolveContainerTypesAfterAssignedExprEval(
		NodeScopeResolver $nodeScopeResolver,
		Expr $var,
		ExpressionResult $varResult,
		MutatingScope $postEvalScope,
		?MutatingScope $preEvalScope,
		ExpressionResultStorage $storage,
	): array
	{
		if ($var instanceof Variable && is_string($var->name)) {
			// A superglobal keeps composing over the pre-eval view: a volatile
			// invalidation between the walk and this read (any maybe-impure call
			// in the assigned expression) would otherwise degrade the write to
			// the raw superglobal array (see bug-14999).
			if (
				!in_array($var->name, Scope::SUPERGLOBAL_VARIABLES, true)
				&& !$varResult->askScopeVariableStateMatches($postEvalScope, false)
			) {
				// the assigned expression reassigned the root variable itself
				return [
					$postEvalScope->getVariableType($var->name),
					$postEvalScope->doNotTreatPhpDocTypesAsCertain()->getVariableType($var->name),
				];
			}

			return [$varResult->getType(), $varResult->getNativeType()];
		}

		if ($postEvalScope->hasExpressionType($var)->yes()) {
			return [
				$varResult->getTypeOnScope($postEvalScope, false),
				$varResult->getTypeOnScope($postEvalScope, true),
			];
		}

		if ($preEvalScope !== null && $preEvalScope->hasExpressionType($var)->yes()) {
			// a fetch the assigned expression invalidated (tracked before the
			// eval, untracked after) - re-price it at the post-eval position
			$reprocessed = $nodeScopeResolver->processExprOnDemand($var, $postEvalScope, $storage->duplicate());

			return [$reprocessed->getType(), $reprocessed->getNativeType()];
		}

		// untracked throughout - nothing the assigned expression did could have
		// changed what the walk read
		return [$varResult->getType(), $varResult->getNativeType()];
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
	 * @param ImpurePoint[] $rhsImpurePoints
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processSureTypesForConditionalExpressionsAfterAssign(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, string $variableName, array $conditionalExpressions, SpecifiedTypes $specifiedTypes, Type $variableType, array $rhsImpurePoints, Expr $assignedExpr, ?ExpressionResult $assignedValueResult): array
	{
		foreach ($specifiedTypes->getSureTypes() as $exprString => [$expr, $exprType]) {
			if (!$this->isExprSafeToProjectThroughVariable($expr, $variableName, $rhsImpurePoints, $assignedExpr)) {
				continue;
			}

			if ($expr instanceof IssetExpr) {
				$innerExpr = $expr->getExpr();
				$conditionalExpressions = $this->addConditionalExpressionHolder(
					$conditionalExpressions,
					$variableName,
					$variableType,
					$innerExpr,
					$this->exprPrinter->printExpr($innerExpr),
					$this->currentTypeForConditionalHolder($nodeScopeResolver, $scope, $storage, $innerExpr, $assignedExpr, $assignedValueResult),
					TrinaryLogic::createMaybe(),
				);
				continue;
			}

			$exprString = (string) $exprString;

			$conditionalExpressions = $this->addConditionalExpressionHolder(
				$conditionalExpressions,
				$variableName,
				$variableType,
				$expr,
				$exprString,
				TypeCombinator::intersect($this->currentTypeForConditionalHolder($nodeScopeResolver, $scope, $storage, $expr, $assignedExpr, $assignedValueResult), $exprType),
				TrinaryLogic::createYes(),
			);
		}

		return $conditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param ImpurePoint[] $rhsImpurePoints
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processSureNotTypesForConditionalExpressionsAfterAssign(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, string $variableName, array $conditionalExpressions, SpecifiedTypes $specifiedTypes, Type $variableType, array $rhsImpurePoints, Expr $assignedExpr, ?ExpressionResult $assignedValueResult): array
	{
		foreach ($specifiedTypes->getSureNotTypes() as $exprString => [$expr, $exprType]) {
			if (!$this->isExprSafeToProjectThroughVariable($expr, $variableName, $rhsImpurePoints, $assignedExpr)) {
				continue;
			}

			if ($expr instanceof IssetExpr) {
				$innerExpr = $expr->getExpr();
				$conditionalExpressions = $this->addConditionalExpressionHolder(
					$conditionalExpressions,
					$variableName,
					$variableType,
					$innerExpr,
					$this->exprPrinter->printExpr($innerExpr),
					new NeverType(),
					TrinaryLogic::createNo(),
				);
				continue;
			}

			$exprString = (string) $exprString;

			$conditionalExpressions = $this->addConditionalExpressionHolder(
				$conditionalExpressions,
				$variableName,
				$variableType,
				$expr,
				$exprString,
				TypeCombinator::remove($this->currentTypeForConditionalHolder($nodeScopeResolver, $scope, $storage, $expr, $assignedExpr, $assignedValueResult), $exprType),
				TrinaryLogic::createYes(),
			);
		}

		return $conditionalExpressions;
	}

	/**
	 * Current type of a conditional-holder expression, used to refine the holder's
	 * projected type. Prefers the tracked scope state over the stored result,
	 * which can be stale after a by-ref write - e.g.
	 * preg_match($p, $s, $matches) updates $matches in the scope state but leaves the
	 * stored result from the earlier `$matches = []` untouched, so reading it back would
	 * intersect the matched shape against the stale array{} and collapse to NEVER.
	 */
	private function currentTypeForConditionalHolder(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, Expr $expr, Expr $assignedExpr, ?ExpressionResult $assignedValueResult): Type
	{
		// A by-ref write lands in the variable's tracked type, so read it from the
		// scope state (getVariableType is null-safe for superglobals/undefined too).
		// Method calls and other non-variable holder exprs have no by-ref hazard and
		// keep reading their stored result.
		if ($expr instanceof Variable && is_string($expr->name) && $scope->hasVariableType($expr->name)->yes()) {
			return $scope->getVariableType($expr->name);
		}

		// the assigned expression's own result is threaded in by the caller - its
		// processing is still in flight, so an on-demand walk would re-enter it
		if ($expr === $assignedExpr && $assignedValueResult !== null) {
			return $assignedValueResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		}

		// holder exprs are usually subexpressions of the walked condition - read
		// them from the walk's own storage (the scope's storage stack misses it
		// on loop-convergence passes); synthetic terms narrowing extensions built
		// (@phpstan-assert property fetches etc.) answer from scope state or a walk
		$storedResult = $storage->findExpressionResult($expr);
		if ($storedResult !== null) {
			return $storedResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		}

		return $nodeScopeResolver->readScopeStateOrSyntheticType($expr, $scope);
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function addConditionalExpressionHolder(
		array $conditionalExpressions,
		string $variableName,
		Type $variableType,
		Expr $holderExpr,
		string $holderExprString,
		Type $holderType,
		TrinaryLogic $holderCertainty,
	): array
	{
		if (!isset($conditionalExpressions[$holderExprString])) {
			$conditionalExpressions[$holderExprString] = [];
		}

		$holder = new ConditionalExpressionHolder([
			'$' . $variableName => ExpressionTypeHolder::createYes(new Variable($variableName), $variableType),
		], new ExpressionTypeHolder(
			$holderExpr,
			$holderType,
			$holderCertainty,
		));
		$conditionalExpressions[$holderExprString][$holder->getKey()] = $holder;

		return $conditionalExpressions;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, ConditionalExpressionHolder[]> $newConditionalExpressions
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function mergeConditionalExpressions(array $conditionalExpressions, array $newConditionalExpressions): array
	{
		foreach ($newConditionalExpressions as $exprString => $holders) {
			foreach ($holders as $key => $holder) {
				$conditionalExpressions[$exprString][$key] = $holder;
			}
		}

		return $conditionalExpressions;
	}

	/**
	 * Recovers the relationship between a `$var = match (...) { … }` result and the
	 * match subject. Each arm body is assigned to `$var` inside the scope where the
	 * subject is narrowed to that arm's condition, then the per-arm scopes are merged
	 * the same way an equivalent `if`/`elseif`/`else` chain would be — reusing
	 * MutatingScope's merge machinery so the resulting conditional-expression holders
	 * are identical to the `if` form. A later narrowing of `$var` then narrows the
	 * subject accordingly.
	 *
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	private function processMatchForConditionalExpressionsAfterAssign(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		string $variableName,
		Match_ $expr,
	): array
	{
		// the pairs were captured while the match (the assigned expression) was
		// processed just above - no arm re-walk
		$armScopesAndTypes = $this->matchHandler->getCapturedArmScopesAndTypes($expr);
		if ($armScopesAndTypes === null || count($armScopesAndTypes) < 2) {
			return [];
		}

		$armScopes = [];
		foreach ($armScopesAndTypes as [$armScope, $armType]) {
			$armScopes[] = $armScope->assignVariable(
				$variableName,
				$armType,
				$armType,
				TrinaryLogic::createYes(),
			);
		}

		$mergedScope = $armScopes[0];
		for ($i = 1, $count = count($armScopes); $i < $count; $i++) {
			$mergedScope = $armScopes[$i]->mergeWith($mergedScope, true);
		}

		$existingConditionalExpressions = $scope->getConditionalExpressions();
		$newConditionalExpressions = [];
		foreach ($mergedScope->getConditionalExpressions() as $exprString => $holders) {
			foreach ($holders as $key => $holder) {
				if (isset($existingConditionalExpressions[$exprString][$key])) {
					continue;
				}
				$newConditionalExpressions[$exprString][$key] = $holder;
			}
		}

		return $newConditionalExpressions;
	}

	/**
	 * We're about to remember "when $variableName is truthy/falsy, $expr has a narrower type".
	 * Whether that's safe to project forward depends on whether re-evaluating $expr later will
	 * still return the same value as when we observed the narrowing — i.e. whether $expr is
	 * referentially transparent with respect to the intervening code.
	 *
	 * Scalar/const-fetch literals are never narrowing targets, so skip them up front (they also
	 * happen to stringify to numeric exprStrings which collide with PHP's numeric-string
	 * array-key autocast).
	 *
	 * A plain variable is always safe: reading it doesn't produce side effects, and if it gets
	 * reassigned the existing conditional-expression-holder machinery invalidates the binding.
	 * This case matters for e.g. `$ok = preg_match(..., $matches); if ($ok) { use $matches }` —
	 * `preg_match` itself has impure points, but `$matches` is a plain variable and the
	 * narrowing attached to it should still survive.
	 *
	 * Other common tracked expressions (property/dim fetches, function/method calls) can always
	 * carry narrowings: PHPStan already memoises their types per exprString, and condition
	 * checks like `$x = $obj->foo() !== null; if ($x) { $obj->foo(); }` rely on this even when
	 * the RHS itself has impure points (as a method call without @phpstan-pure always does).
	 *
	 * Anything else is accepted only when the right-hand side evaluation recorded zero impure
	 * points — in that case all sub-expressions it produced sure types for were evaluated
	 * without side effects and can be re-evaluated later with the same result.
	 *
	 * @param ImpurePoint[] $rhsImpurePoints
	 */
	private function isExprSafeToProjectThroughVariable(Expr $expr, string $variableName, array $rhsImpurePoints, Expr $assignedExpr): bool
	{
		if ($expr instanceof IssetExpr) {
			return $this->isExprSafeToProjectThroughVariable($expr->getExpr(), $variableName, $rhsImpurePoints, $assignedExpr);
		}

		// Scalar/const-fetch literals and PHPStan virtual nodes (e.g. NativeTypeExpr) are never
		// narrowing targets at a usage site — skip them so they don't collide with PHP's
		// numeric-string array-key autocast or leak internal virtual expressions into the
		// conditional-expression map.
		if ($expr instanceof Node\Scalar || $expr instanceof ConstFetch || $expr instanceof VirtualNode || $expr instanceof Expr\UnaryMinus && $expr->expr instanceof Node\Scalar) {
			return false;
		}

		if ($expr instanceof Variable) {
			return is_string($expr->name) && $expr->name !== $variableName;
		}

		if (
			$expr instanceof PropertyFetch
			|| $expr instanceof ArrayDimFetch
		) {
			return true;
		}

		if (
			$expr instanceof FuncCall
			|| $expr instanceof MethodCall
			|| $expr instanceof NullsafeMethodCall
			|| $expr instanceof StaticCall
		) {
			// A call's type can change between evaluations. We're willing to project the
			// narrowing through a stored boolean only when the sure-type expression is a
			// *sub*-expression of the assigned RHS — e.g. `$ok = $x->foo() !== null` builds
			// a sure type for the sub-call `$x->foo()`. In that case the RHS as a whole
			// carries the comparison result, and later `if ($ok)` usefully re-narrows the
			// remembered sub-call. When the sure-type expression IS the whole RHS (e.g.
			// `$device = $this->nullable(); if ($device === null) { … }` with the
			// falsey-scalar loop producing `$this->nullable() === null` narrowings), the
			// projection would survive across subsequent reassignments of the target
			// expression and wrongly re-narrow fresh calls — so skip it.
			return $expr !== $assignedExpr;
		}

		return count($rhsImpurePoints) === 0;
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

	private function processArrayByRefItems(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, string $rootVarName, Expr\Array_ $arrayExpr, Expr $parentExpr): MutatingScope
	{
		$implicitIndex = 0;
		foreach ($arrayExpr->items as $arrayItem) {
			if ($arrayItem->key !== null) {
				// the key was walked as part of the assigned array literal
				$keyType = $nodeScopeResolver->readStoredResult($arrayItem->key, $storage)->getTypeOnScope($scope, $scope->nativeTypesPromoted)->toArrayKey();

				if ($implicitIndex !== null) {
					$keyValues = $keyType->getConstantScalarValues();
					if (count($keyValues) === 1) {
						$keyValue = $keyValues[0];
						if (is_int($keyValue) && $keyValue >= $implicitIndex) {
							$implicitIndex = $keyValue + 1;
						}
					} elseif (!$keyType->isInteger()->no()) {
						// Key could be an integer, but we don't know which one,
						// so subsequent implicit indices are unpredictable
						$implicitIndex = null;
					}
				}

				$dimExpr = $arrayItem->key;
			} elseif ($implicitIndex !== null) {
				$dimExpr = new Node\Scalar\Int_($implicitIndex);
				$implicitIndex++;
			} else {
				$dimExpr = new TypeExpr(new IntegerType());
			}

			if ($arrayItem->value instanceof Expr\Array_) {
				$dimFetchExpr = new ArrayDimFetch($parentExpr, $dimExpr);
				$scope = $this->processArrayByRefItems($nodeScopeResolver, $scope, $storage, $rootVarName, $arrayItem->value, $dimFetchExpr);
			}

			if (!$arrayItem->byRef || !$arrayItem->value instanceof Variable || !is_string($arrayItem->value->name)) {
				continue;
			}

			$refVarName = $arrayItem->value->name;
			$dimFetchExpr = new ArrayDimFetch($parentExpr, $dimExpr);
			// a plain variable read is scope state - no need to price a synthetic
			// Variable node on demand (mirrors VariableHandler's typeCallback)
			$nativeScope = $scope->doNotTreatPhpDocTypesAsCertain();
			$refType = $scope->hasVariableType($refVarName)->no() ? new ErrorType() : $scope->getVariableType($refVarName);
			$refNativeType = $nativeScope->hasVariableType($refVarName)->no() ? new ErrorType() : $nativeScope->getVariableType($refVarName);

			// When $rootVarName's array key changes, update $refVarName
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($rootVarName, new Variable($refVarName), $dimFetchExpr),
				$refType,
				$refNativeType,
			);

			// When $refVarName changes, update $rootVarName's array key
			$scope = $scope->assignExpression(
				new IntertwinedVariableByReferenceWithExpr($refVarName, $dimFetchExpr, new Variable($refVarName)),
				$refType,
				$refNativeType,
			);
		}

		return $scope;
	}

	private const ARRAY_DIM_FETCH_WRITE_DEPTH_LIMIT = 5;

	/**
	 * @param non-empty-list<ArrayDimFetch> $dimFetchStack
	 * @param non-empty-list<array{Type|null, ArrayDimFetch}> $offsetTypes
	 *
	 * @return array{Type, list<array{Expr, Type}>}
	 */
	private function produceArrayDimFetchAssignValueToWrite(NodeScopeResolver $nodeScopeResolver, array $dimFetchStack, array $offsetTypes, Type $offsetValueType, Type $valueToWrite, MutatingScope $scope, ExpressionResultStorage $storage): array
	{
		$originalValueToWrite = $valueToWrite;

		$offsetValueTypeStack = [$offsetValueType];
		$generalizeOnWrite = $offsetTypes[array_key_last($offsetTypes)][0] !== null;
		$dimDepth = 0;
		foreach (array_slice($offsetTypes, 0, -1) as [$offsetType, $dimFetch]) {
			$dimDepth++;
			if ($offsetType === null) {
				$offsetValueType = new ConstantArrayType([], []);
				$generalizeOnWrite = false;
			} else {
				if ($dimDepth > self::ARRAY_DIM_FETCH_WRITE_DEPTH_LIMIT && $offsetValueType->isOversizedArray()->yes()) {
					$offsetValueType = new MixedType();
				} else {
					$has = $offsetValueType->hasOffsetValueType($offsetType);
					if ($has->yes()) {
						if ($scope->hasExpressionType($dimFetch)->yes()) {
							$offsetValueType = $scope->getStateType($dimFetch);
						} else {
							$offsetValueType = $offsetValueType->getOffsetValueType($offsetType);
						}
					} elseif ($has->maybe()) {
						if ($scope->hasExpressionType($dimFetch)->yes()) {
							$generalizeOnWrite = false;
							$offsetValueType = $scope->getStateType($dimFetch);
						} else {
							$offsetValueType = TypeCombinator::union($offsetValueType->getOffsetValueType($offsetType), new ConstantArrayType([], []));
						}
					} else {
						$generalizeOnWrite = false;
						$offsetValueType = new ConstantArrayType([], []);
					}
				}
			}

			$offsetValueTypeStack[] = $offsetValueType;
		}

		$lastDimKey = array_key_last($dimFetchStack);
		$computedContainerValues = [];
		foreach (array_reverse($offsetTypes) as $i => [$offsetType]) {
			/** @var Type $offsetValueType */
			$offsetValueType = array_pop($offsetValueTypeStack);
			if (
				!$offsetValueType instanceof MixedType
				&& !$offsetValueType->isArray()->yes()
			) {
				if ($offsetType !== null && $offsetType->isInteger()->yes()) {
					$offsetValueType = TypeCombinator::intersect($offsetValueType, StaticTypeFactory::intOffsetAccessibleType());
				} else {
					$offsetValueType = TypeCombinator::intersect($offsetValueType, StaticTypeFactory::generalOffsetAccessibleType());
				}
			}

			$arrayDimFetch = $dimFetchStack[$i] ?? null;
			if (
				$offsetType !== null
				&& $arrayDimFetch !== null
				&& $scope->hasExpressionType($arrayDimFetch)->yes()
				&& !$offsetValueType->hasOffsetValueType($offsetType)->no()
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
				// when $unionValues=false the array item-type will be replaced with $valueToWrite
				// when $unionValues=true the existing array item-type will be union'ed with $valueToWrite -> type gets wider
				$unionValues = false;
				if ($i === 0) {
					$unionValues = true;
				} elseif (
					$generalizeOnWrite
					&& $i === count($offsetTypes) - 1
					&&
						(
							$originalValueToWrite->isConstantScalarValue()->yes()
							|| !$offsetValueType->getIterableValueType()->isSuperTypeOf($valueToWrite)->yes()
						)
				) {
					$unionValues = true;
				}

				$valueToWrite = $offsetValueType->setOffsetValueType($offsetType, $valueToWrite, $unionValues);
			}

			if ($arrayDimFetch !== null && $offsetValueType->isList()->yes() && $this->shouldKeepList($nodeScopeResolver, $arrayDimFetch, $scope, $storage, $offsetValueType)) {
				$valueToWrite = TypeCombinator::intersect($valueToWrite, new AccessoryArrayListType());
			}

			$containerKey = $lastDimKey - $i - 1;
			if ($containerKey < 0) {
				continue;
			}

			$computedContainerValues[$containerKey] = $valueToWrite;
		}

		$additionalExpressions = [];
		foreach ($dimFetchStack as $key => $dimFetch) {
			if ($dimFetch->dim === null) {
				continue;
			}

			if ($key === $lastDimKey) {
				$additionalValueType = $originalValueToWrite;
			} elseif (isset($computedContainerValues[$key])) {
				$additionalValueType = $computedContainerValues[$key];
			} else {
				// the dimension's walk-captured type, aligned with the stack by key
				$offsetType = $offsetTypes[$key][0];
				if ($offsetType === null) {
					throw new ShouldNotHappenException();
				}
				$additionalValueType = $valueToWrite->getOffsetValueType($offsetType);
			}

			$additionalExpressions[] = [$dimFetch, $additionalValueType];
		}

		return [$valueToWrite, $additionalExpressions];
	}

	private function shouldKeepList(NodeScopeResolver $nodeScopeResolver, ArrayDimFetch $arrayDimFetch, MutatingScope $scope, ExpressionResultStorage $storage, Type $offsetValueType): bool
	{
		if ($arrayDimFetch->dim instanceof Expr\BinaryOp\Plus) {
			if ( // keep list for $list[$index + 1] assignments
				$arrayDimFetch->dim->right instanceof Variable
				&& $arrayDimFetch->dim->left instanceof Node\Scalar\Int_
				&& $arrayDimFetch->dim->left->value === 1
				&& $scope->hasExpressionType(new ArrayDimFetch($arrayDimFetch->var, $arrayDimFetch->dim->right))->yes()
			) {
				return true;
			} elseif ( // keep list for $list[1 + $index] assignments
				$arrayDimFetch->dim->left instanceof Variable
				&& $arrayDimFetch->dim->right instanceof Node\Scalar\Int_
				&& $arrayDimFetch->dim->right->value === 1
				&& $scope->hasExpressionType(new ArrayDimFetch($arrayDimFetch->var, $arrayDimFetch->dim->left))->yes()
			) {
				return true;
			}
		} elseif ( // keep list for $list[count($list) - n] assignments
			$arrayDimFetch->dim instanceof Expr\BinaryOp\Minus
			&& $arrayDimFetch->dim->right instanceof Node\Scalar\Int_
			&& $arrayDimFetch->dim->left instanceof Expr\FuncCall
			&& $arrayDimFetch->dim->left->name instanceof Name
			&& in_array($arrayDimFetch->dim->left->name->toLowerString(), ['count', 'sizeof'], true)
			&& count($arrayDimFetch->dim->left->getArgs()) === 1 // could support COUNT_RECURSIVE, COUNT_NORMAL
			&& $this->isSameVariable($arrayDimFetch->var, $arrayDimFetch->dim->left->getArgs()[0]->value)
			// the dimension was walked as part of the assign target chain
			&& IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($nodeScopeResolver->readStoredResult($arrayDimFetch->dim, $storage)->getTypeOnScope($scope, $scope->nativeTypesPromoted))->yes()
			&& $offsetValueType->isIterableAtLeastOnce()->yes()
		) {
			return true;
		} elseif ( // keep list for $list[array_key_last($list)] and $list[array_key_first($list)] assignments
			$arrayDimFetch->dim instanceof Expr\FuncCall
			&& $arrayDimFetch->dim->name instanceof Name
			&& in_array($arrayDimFetch->dim->name->toLowerString(), ['array_key_last', 'array_key_first'], true)
			&& count($arrayDimFetch->dim->getArgs()) >= 1
			&& $this->isSameVariable($arrayDimFetch->var, $arrayDimFetch->dim->getArgs()[0]->value)
		) {
			return true;
		} elseif ( // keep list for $list[array_search($needle, $list)] assignments
			$arrayDimFetch->dim instanceof Expr\FuncCall
			&& $arrayDimFetch->dim->name instanceof Name
			&& $arrayDimFetch->dim->name->toLowerString() === 'array_search'
			&& count($arrayDimFetch->dim->getArgs()) >= 2 // the haystack is the second argument
			&& $this->isSameVariable($arrayDimFetch->var, $arrayDimFetch->dim->getArgs()[1]->value)
		) {
			return true;
		}

		return false;
	}

	private function isSameVariable(Expr $a, Expr $b): bool
	{
		if ($a instanceof Variable && $b instanceof Variable && is_string($a->name) && is_string($b->name)) {
			return $a->name === $b->name;
		}

		return false;
	}

	/**
	 * Returns the property's readable (declared) type, filtered down to the union
	 * members that are not disjoint from the currently narrowed property type.
	 */
	private function getOriginalPropertyType(NodeScopeResolver $nodeScopeResolver, PropertyFetch|StaticPropertyFetch $propertyFetch, MutatingScope $scope): Type
	{
		// the fetch is a write target inside an offset chain - nothing of it is
		// processed yet, so the holder type is read maybe-stored (a plain variable
		// receiver like $this answers from scope state without a walk)
		if ($propertyFetch instanceof PropertyFetch) {
			$propertyHolderType = $nodeScopeResolver->readTypeOfMaybeStored($propertyFetch->var, $scope);
		} elseif ($propertyFetch->class instanceof Name) {
			$propertyHolderType = $scope->resolveTypeByName($propertyFetch->class);
		} else {
			$propertyHolderType = $nodeScopeResolver->readTypeOfMaybeStored($propertyFetch->class, $scope);
		}
		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNodeWithHolderType($propertyFetch, $propertyHolderType, $scope);
		$originalPropertyType = $propertyReflection !== null ? $propertyReflection->getReadableType() : new ErrorType();
		if ($originalPropertyType instanceof UnionType) {
			$currentPropertyType = $nodeScopeResolver->readTypeOfMaybeStored($propertyFetch, $scope);
			$originalPropertyType = $originalPropertyType->filterTypes(static fn (Type $innerType) => !$innerType->isSuperTypeOf($currentPropertyType)->no());
		}

		return $originalPropertyType;
	}

}
