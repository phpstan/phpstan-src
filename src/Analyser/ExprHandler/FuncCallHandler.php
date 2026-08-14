<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgsResult;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\DynamicReturnTypeStoragePrimer;
use PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper;
use PHPStan\Analyser\ExprHandler\Helper\OutputBufferHelper;
use PHPStan\Analyser\GatheringNodeCallback;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Throwable;
use WeakReference;
use function array_filter;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function is_string;
use function sprintf;
use function str_starts_with;

/**
 * @implements ExprHandler<FuncCall>
 */
#[AutowiredService]
final class FuncCallHandler implements ExprHandler
{

	/**
	 * @param ExtensionsCollection<DynamicFunctionThrowTypeExtension> $dynamicFunctionThrowTypeExtensions
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredExtensions(of: DynamicFunctionThrowTypeExtension::class)]
		private ExtensionsCollection $dynamicFunctionThrowTypeExtensions,
		private DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
		private ExpressionResultFactory $expressionResultFactory,
		private TypeSpecifier $typeSpecifier,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private EarlyTerminatingCallHelper $earlyTerminatingHelper,
		private DynamicReturnTypeStoragePrimer $storagePrimer,
		private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof FuncCall && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$parametersAcceptor = null;
		$variants = [];
		$namedArgumentsVariants = null;
		$functionReflection = null;
		$nameResult = null;
		$throwPoints = [];
		$impurePoints = [];
		// A call configured as early-terminating never returns: give it an explicit
		// never so the statement's exit point follows from the result type, instead of
		// NodeScopeResolver re-deriving it via Scope::getType().
		$isEarlyTerminating = $expr->name instanceof Name
			&& $this->earlyTerminatingHelper->isEarlyTerminatingFunctionCall($expr->name->toString());
		$isAlwaysTerminating = $isEarlyTerminating;
		if ($expr->name instanceof Expr) {
			// process the dynamic callee name first, then consume its type (single-pass
			// inside-out) rather than reading it before processExprNode() stores it
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$nameType = $nameResult->getType();
			if (!$nameType->isCallable()->no()) {
				$variants = $nameType->getCallableParametersAcceptors($scope);
				// A structural acceptor (names/positions/variadic) drives the per-arg
				// metadata and the throw/impure points - generics are resolved
				// type-driven by processArgs() into $resolvedParametersAcceptor.
				$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization($expr->getArgs(), $variants, null);
			}

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

				$scope = $nodeScopeResolver->processImmediatelyCalledCallable($scope, $parametersAcceptor->getInvalidateExpressions(), $parametersAcceptor->getUsedVariables());
			}
		} elseif ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$variants = $functionReflection->getVariants();
			$namedArgumentsVariants = $functionReflection->getNamedArgumentsVariants();
			// A structural acceptor (names/positions/variadic) drives argument
			// normalization, the impure point and the throw points - generics are
			// resolved type-driven by processArgs() into $resolvedParametersAcceptor.
			$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization($expr->getArgs(), $variants, $namedArgumentsVariants);
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
			// process the clone arguments as reads so the cloned object and the
			// properties array resolve from stored results instead of unprocessed
			// nodes; processArgs() below processes them again as clone()'s arguments,
			// so the NoopNodeCallback here avoids duplicate node-callbacks.
			$nodeScopeResolver->processExprNode($stmt, $normalizedExpr->getArgs()[0]->value, $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
			$clonePropertiesArgResult = $nodeScopeResolver->processExprNode($stmt, $normalizedExpr->getArgs()[1]->value, $scope, $storage, new NoopNodeCallback(), $context->enterDeep());
			$clonePropertiesArgType = $clonePropertiesArgResult->getType();
			$cloneExpr = new TypeExpr($scope->getType(new Expr\Clone_($normalizedExpr->getArgs()[0]->value)));
			$clonePropertiesArgTypeConstantArrays = $clonePropertiesArgType->getConstantArrays();
			foreach ($clonePropertiesArgTypeConstantArrays as $clonePropertiesArgTypeConstantArray) {
				foreach ($clonePropertiesArgTypeConstantArray->getKeyTypes() as $i => $clonePropertyKeyType) {
					$clonePropertyKeyTypeScalars = $clonePropertyKeyType->getConstantScalarValues();
					$propertyAttributes = $normalizedExpr->getAttributes();
					$propertyAttributes['inCloneWith'] = true;
					if (count($clonePropertyKeyTypeScalars) === 1) {
						$nodeScopeResolver->processVirtualAssign(
							$scope,
							$storage,
							$stmt,
							new PropertyFetch($cloneExpr, (string) $clonePropertyKeyTypeScalars[0], $propertyAttributes),
							new TypeExpr($clonePropertiesArgTypeConstantArray->getValueTypes()[$i]),
							$nodeCallback,
						);
						continue;
					}

					$nodeScopeResolver->processVirtualAssign(
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

		/** @var array{Type, Type}|null $arrayWalkValueTypes */
		$arrayWalkValueTypes = null;
		$arrayWalkArrayArg = null;
		$arrayWalkOriginalArrayType = null;
		$arrayWalkOriginalArrayNativeType = null;
		$nodeCallbackForArgs = $nodeCallback;
		if (
			$functionReflection !== null
			&& $functionReflection->getName() === 'array_walk'
			&& count($normalizedExpr->getArgs()) >= 2
		) {
			$callbackArg = $normalizedExpr->getArgs()[1]->value;
			$firstParamName = null;

			if (
				$callbackArg instanceof Expr\Closure
				&& isset($callbackArg->params[0])
				&& $callbackArg->params[0]->byRef
				&& $callbackArg->params[0]->var instanceof Variable
				&& is_string($callbackArg->params[0]->var->name)
			) {
				$firstParamName = $callbackArg->params[0]->var->name;
			}

			if ($firstParamName !== null) {
				$arrayWalkArrayArg = $normalizedExpr->getArgs()[0]->value;

				$nodeCallbackForArgs = new GatheringNodeCallback(static function (Node $node, Scope $scope) use ($callbackArg, $firstParamName, &$arrayWalkValueTypes): void {
					if (!($node instanceof ClosureReturnStatementsNode) || $node->getClosureExpr() !== $callbackArg) {
						return;
					}

					$types = [];
					$nativeTypes = [];
					$stmtResult = $node->getStatementResult();
					foreach ($stmtResult->getExitPoints() as $exitPoint) {
						$exitScope = $exitPoint->getScope();
						if (!$exitScope->hasVariableType($firstParamName)->yes()) {
							continue;
						}

						$types[] = $exitScope->getVariableType($firstParamName);
						$nativeTypes[] = $exitScope->getNativeType(new Variable($firstParamName));
					}
					if (!$stmtResult->isAlwaysTerminating()) {
						$stmtScope = $stmtResult->getScope();
						if ($stmtScope->hasVariableType($firstParamName)->yes()) {
							$types[] = $stmtScope->getVariableType($firstParamName);
							$nativeTypes[] = $stmtScope->getNativeType(new Variable($firstParamName));
						}
					}
					if (count($types) <= 0) {
						return;
					}

					$arrayWalkValueTypes = [
						TypeCombinator::union(...$types),
						TypeCombinator::union(...$nativeTypes),
					];
				}, $nodeCallback);
			}
		}

		$scopeBeforeArgs = $scope;
		$argsResult = $nodeScopeResolver->processArgs($stmt, $functionReflection, null, $variants, $namedArgumentsVariants, $normalizedExpr, $scope, $storage, $nodeCallbackForArgs, $context);
		$resolvedParametersAcceptor = $argsResult->getResolvedParametersAcceptor();
		$scope = $argsResult->getScope();
		$nodeScopeResolver->processDroppedArgs($stmt, $expr, $normalizedExpr, $scope, $storage, $context);
		$hasYield = $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		if ($functionReflection !== null) {
			// created after the args were processed - the side-effect flip
			// parameters (print_r's $return, ...) read an argument's type, which
			// is only available once its result is stored
			$impurePoint = SimpleImpurePoint::createFromVariant($functionReflection, $parametersAcceptor, $scope, $expr->getArgs());
			if ($impurePoint !== null) {
				$impurePoints[] = new ImpurePoint($scopeBeforeArgs, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
			}
		}

		if ($arrayWalkValueTypes !== null && $arrayWalkArrayArg !== null) {
			$arrayWalkOriginalArrayType = $scope->getType($arrayWalkArrayArg);
			$arrayWalkOriginalArrayNativeType = $scope->getNativeType($arrayWalkArrayArg);
			$arrayWalkValueType = $arrayWalkValueTypes[0];
			$arrayWalkValueNativeType = $arrayWalkValueTypes[1];
			$newArrayType = $arrayWalkOriginalArrayType->mapValueType(static fn (Type $type): Type => $arrayWalkValueType);
			$newArrayNativeType = $arrayWalkOriginalArrayNativeType->mapValueType(static fn (Type $type): Type => $arrayWalkValueNativeType);

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayWalkArrayArg,
				new NativeTypeExpr($newArrayType, $newArrayNativeType),
				$nodeCallback,
			)->getScope();
		}

		// The return type is derived from $resolvedParametersAcceptor - the acceptor
		// processArgs() selected from the arg types gathered on the arg-to-arg
		// evolving scope (type-driven, generics resolved). When null
		// (native-types-promoted, on-demand / synthetic pricing, or special cases
		// inside resolveReturnType), the acceptor is re-derived from the
		// already-processed argument results on the asking scope.
		$storageRef = WeakReference::create($storage);
		$typeCallback = $isEarlyTerminating
			? static fn (bool $nativeTypesPromoted): Type => new NeverType(true)
			: function (bool $nativeTypesPromoted) use ($nodeScopeResolver, $beforeScope, $expr, $nameResult, $resolvedParametersAcceptor, $argsResult, $storageRef): Type {
				// for always-true/always-false type checks the call's own narrowing
				// (already produced as this result's specifyTypesCallback) decides
				// the return type - the verdict is a read of that narrowing, not a
				// second derivation. The result is looked up through a weak storage
				// reference: this callback is owned by that very result, and a
				// strong backedge would be a cycle (PHPStan runs with gc_disable()).
				if (
					!$nativeTypesPromoted
					&& $expr->name instanceof Name
					&& in_array($expr->name->toLowerString(), ['array_key_exists', 'key_exists', 'in_array', 'is_subclass_of'], true)
				) {
					$callStorage = $storageRef->get();
					$callResult = $callStorage === null ? null : $callStorage->findExpressionResult($expr);
					if ($callResult !== null) {
						$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($beforeScope, $expr, $callResult, $argsResult);
						if ($isAlways !== null) {
							return new ConstantBooleanType($isAlways);
						}
					}
				}

				return $this->resolveReturnType(
					$nodeScopeResolver,
					$beforeScope,
					$nativeTypesPromoted,
					$expr,
					$nameResult,
					$nativeTypesPromoted ? null : $resolvedParametersAcceptor,
					$argsResult,
				);
			};
		$specifyTypesCallback = fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted): SpecifiedTypes => $this->specifyTypes(
			$nodeScopeResolver,
			$nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope,
			$expr,
			$normalizedExpr,
			$nameResult,
			$resolvedParametersAcceptor,
			$specifyContext,
			$argsResult,
		);

		// A type constraint on a (narrowable, i.e. non-side-effecting, non-first-class)
		// function call narrows the call itself - the inside-out equivalent of
		// createForExpr's FuncCall purity gate + tail entry. An impure call narrows to
		// nothing.
		$createTypesCallback = function (Type $type, TypeSpecifierContext $createContext, bool $nativeTypesPromoted) use ($nodeScopeResolver, $expr, $nameResult, $beforeScope, $argsResult): SpecifiedTypes {
			$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
			if (!$this->isFuncCallNarrowable($nodeScopeResolver, $s, $expr, $nameResult)) {
				return new SpecifiedTypes([], []);
			}

			$types = $this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $createContext);

			// array_key_first/array_key_last/array_find_key return null iff the
			// array has no matching key - a null constraint on the call narrows
			// the array argument (both directions for first/last, non-empty only
			// for find_key: an empty result does not mean an empty array)
			if (
				$expr->name instanceof Name
				&& !$expr->isFirstClassCallable()
				&& isset($expr->getArgs()[0])
				&& $type->isNull()->yes()
			) {
				$funcName = $expr->name->toLowerString();
				$bothDirections = in_array($funcName, ['array_key_first', 'array_key_last'], true);
				if ($bothDirections || $funcName === 'array_find_key') {
					$argExpr = $expr->getArgs()[0]->value;
					// the argument was processed with the call; a rewritten call
					// (call_user_func) keys its results by the normalized arg nodes,
					// those fall back to the stored-result read
					$argResult = $argsResult->getArgResult($argExpr);
					$argType = $argResult !== null
						? $argResult->getTypeOnScope($s, $s->nativeTypesPromoted)
						: $nodeScopeResolver->readTypeOfMaybeStored($argExpr, $s);
					if ($argType->isArray()->yes() && ($bothDirections || $createContext->falsey())) {
						$types = $types->unionWith(
							$this->defaultNarrowingHelper->createForSubject($argExpr, new NonEmptyArrayType(), $createContext->negate(), $s),
						);
					}
				}
			}

			return $types;
		};

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw-point return type is computed: getFunctionThrowPoint() resolves the
		// return type through the typeCallback, whose type-check verdict reads this
		// very result's narrowing, and dynamic return type extensions may ask about
		// the call too. Without a stored result those asks would re-process this
		// FuncCall on demand and recurse back into getFunctionThrowPoint(). The
		// callbacks are scope-independent, so the preliminary result answers those
		// asks correctly; finalize() below completes it with the resolved scope and
		// throw/impure points.
		$preliminaryResult = $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: [],
			impurePoints: [],
			typeCallback: $typeCallback,
			specifyTypesCallback: $specifyTypesCallback,
			createTypesCallback: $createTypesCallback,
		);
		$nodeScopeResolver->storeExpressionResult($storage, $expr, $preliminaryResult);

		if ($normalizedExpr->name instanceof Expr) {
			$nameType = $scope->getType($normalizedExpr->name);
			if (
				$nameType->isObject()->yes()
				&& $nameType->isCallable()->yes()
				&& (new ObjectType(Closure::class))->isSuperTypeOf($nameType)->no()
			) {
				$invokeResult = $nodeScopeResolver->processExprNode(
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
			// The call's return type, computed from the already-processed argument
			// results (resolveReturnType reads them from the stored results,
			// never re-running processArgs) - asking Scope::getType() for the
			// FuncCall here would re-enter this handler on demand, as its result is
			// not stored yet.
			// Resolve it through the stored preliminary result so the memoized
			// value seeds the final result below - the first later type read
			// would otherwise run resolveReturnType() again.
			$returnType = $preliminaryResult->getKeepVoidType(false);
			// The early structural check above (line ~180) only sees the unresolved
			// acceptor return type; a conditional-return never (e.g.
			// `($x is Foo ? never : string)`) only resolves to never once the actual
			// argument types are folded in by the type-driven resolved acceptor. Read
			// it from that acceptor's return type, not resolveReturnType(), which
			// folds in call_user_func()/dynamic-extension special cases that must not
			// make the call itself always-terminating (e.g.
			// `call_user_func(fn() => exit())`).
			if ($resolvedParametersAcceptor !== null) {
				$resolvedReturnType = $resolvedParametersAcceptor->getReturnType();
				$isAlwaysTerminating = $isAlwaysTerminating || ($resolvedReturnType instanceof NeverType && $resolvedReturnType->isExplicit());
			}
			$functionThrowPoint = $this->getFunctionThrowPoint($functionReflection, $parametersAcceptor, $returnType, $normalizedExpr, $scope, $context);
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
			&& $this->rememberPossiblyImpureFunctionValues
			&& $functionReflection->hasSideEffects()->maybe()
			&& !$functionReflection->isBuiltin()
		) {
			$scope = $scope->assignExpression(
				new PossiblyImpureCallExpr($normalizedExpr, $normalizedExpr, sprintf('%s()', $functionReflection->getName())),
				$parametersAcceptor->getReturnType(),
				new MixedType(),
			);
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

			$scope = $nodeScopeResolver->processVirtualAssign(
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

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr(
					$this->getArrayFunctionAppendingType($functionReflection, $scopeBeforeArgs, $normalizedExpr, $argsResult),
					$this->getArrayFunctionAppendingType($functionReflection, $scopeBeforeArgs->doNotTreatPhpDocTypesAsCertain(), $normalizedExpr, $argsResult),
				),
				$nodeCallback,
			)->getScope();
		}

		if (
			$functionReflection !== null
			&& in_array($functionReflection->getName(), ['fopen', 'file_get_contents'], true)
		) {
			$scope = $scope->assignVariable('http_response_header', new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()), new AccessoryArrayListType()]), new ArrayType(new IntegerType(), new StringType()), TrinaryLogic::createYes());
		}

		if (
			$functionReflection !== null
			&& $functionReflection->getName() === 'shuffle'
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$scope = $nodeScopeResolver->processVirtualAssign(
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
			$arrayArgResult = $argsResult->getArgResult($arrayArg);
			$arrayArgType = $arrayArgResult !== null ? $arrayArgResult->getType() : $scope->getType($arrayArg);
			$arrayArgNativeType = $arrayArgResult !== null ? $arrayArgResult->getNativeType() : $scope->getNativeType($arrayArg);

			$offsetArg = $normalizedExpr->getArgs()[1]->value;
			$offsetArgResult = $argsResult->getArgResult($offsetArg);
			$offsetType = $offsetArgResult !== null ? $offsetArgResult->getType() : $scopeBeforeArgs->getType($offsetArg);

			if (isset($normalizedExpr->getArgs()[2])) {
				$lengthArg = $normalizedExpr->getArgs()[2]->value;
				$lengthArgResult = $argsResult->getArgResult($lengthArg);
				$lengthType = $lengthArgResult !== null ? $lengthArgResult->getType() : $scopeBeforeArgs->getType($lengthArg);
			} else {
				$lengthType = new NullType();
			}

			if (isset($normalizedExpr->getArgs()[3])) {
				$replacementArg = $normalizedExpr->getArgs()[3]->value;
				$replacementType = $scopeBeforeArgs->getType($replacementArg);
				$replacementNativeType = $scopeBeforeArgs->getNativeType($replacementArg);
			} else {
				$replacementType = new ConstantArrayType([], []);
				$replacementNativeType = new ConstantArrayType([], []);
			}

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr(
					$arrayArgType->spliceArray($offsetType, $lengthType, $replacementType),
					$arrayArgNativeType->spliceArray($offsetType, $lengthType, $replacementNativeType),
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

			$scope = $nodeScopeResolver->processVirtualAssign(
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
			&& in_array($functionReflection->getName(), ['natcasesort', 'natsort', 'arsort', 'asort', 'ksort', 'krsort', 'uasort', 'uksort'], true)
			&& count($normalizedExpr->getArgs()) >= 1
		) {
			$arrayArg = $normalizedExpr->getArgs()[0]->value;

			$scope = $nodeScopeResolver->processVirtualAssign(
				$scope,
				$storage,
				$stmt,
				$arrayArg,
				new NativeTypeExpr($scope->getType($arrayArg)->makeListMaybe(), $scope->getNativeType($arrayArg)->makeListMaybe()),
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

					if (!$optional) {
						$scope = $scope->assignVariable($name, $type, $type, TrinaryLogic::createYes());
					} else {
						$hasVariable = $scope->hasVariableType($name);
						if (!$hasVariable->no()) {
							$type = TypeCombinator::union($scope->getVariableType($name), $type);
						}

						$scope = $scope->assignVariable($name, $type, $type, $scope->hasVariableType($name)->or(TrinaryLogic::createMaybe()));
					}
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

		$outputBufferDelta = $functionReflection !== null ? OutputBufferHelper::getLevelDelta($functionReflection->getName()) : 0;
		if ($outputBufferDelta !== 0) {
			$scope = OutputBufferHelper::applyLevelDelta($scope, $outputBufferDelta);
		}

		$pureCallable = $parametersAcceptor instanceof CallableParametersAcceptor
			&& count($parametersAcceptor->getImpurePoints()) === 0;
		if (
			($functionReflection !== null && !$functionReflection->isBuiltin() && !$functionReflection->hasSideEffects()->no())
			|| ($functionReflection === null && !$pureCallable)
		) {
			$scope = $scope->invalidateVolatileExpressions();
		}

		return $preliminaryResult->finalize($scope, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints);
	}

	private function getFunctionThrowPoint(
		FunctionReflection $functionReflection,
		?ParametersAcceptor $parametersAcceptor,
		Type $returnType,
		FuncCall $normalizedFuncCall,
		MutatingScope $scope,
		ExpressionContext $context,
	): ?InternalThrowPoint
	{
		foreach ($this->dynamicFunctionThrowTypeExtensions->getAll() as $extension) {
			if (!$extension->isFunctionSupported($functionReflection)) {
				continue;
			}

			$throwType = $extension->getThrowTypeFromFunctionCall($functionReflection, $normalizedFuncCall, $scope);
			if ($throwType === null) {
				return null;
			}

			return InternalThrowPoint::createExplicit($scope, $throwType, $normalizedFuncCall, false);
		}

		$throwType = $functionReflection->getThrowType();
		if ($throwType === null) {
			if ($returnType instanceof NeverType && $returnType->isExplicit()) {
				$throwType = new ObjectType(Throwable::class);
			}
		}

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $normalizedFuncCall, true);
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
				|| count($normalizedFuncCall->getArgs()) > 0
			) {
				if (!$context->isInThrow() || !(new ObjectType(Throwable::class))->isSuperTypeOf($returnType)->yes()) {
					return InternalThrowPoint::createImplicit($scope, $normalizedFuncCall);
				}
			}
		}

		return null;
	}

	private function getArrayFunctionAppendingType(FunctionReflection $functionReflection, Scope $scope, FuncCall $expr, ArgsResult $argsResult): Type
	{
		$arrayArg = $expr->getArgs()[0]->value;
		$arrayArgResult = $argsResult->getArgResult($arrayArg);
		// closure args have no ExpressionResult (ProcessClosureResult carries none);
		// they fall back to the scope, every other arg reads its captured result.
		$arrayType = $arrayArgResult !== null ? $arrayArgResult->getTypeOnScope($scope->toMutatingScope(), $scope->toMutatingScope()->nativeTypesPromoted) : $scope->getType($arrayArg);
		$callArgs = array_slice($expr->getArgs(), 1);

		/**
		 * @param Arg[] $callArgs
		 * @param callable(?Type, Type, bool): void $setOffsetValueType
		 */
		$setOffsetValueTypes = static function (Scope $scope, array $callArgs, callable $setOffsetValueType, ?bool &$nonConstantArrayWasUnpacked = null) use ($argsResult): void {
			foreach ($callArgs as $callArg) {
				$callArgResult = $argsResult->getArgResult($callArg->value);
				$callArgType = $callArgResult !== null ? $callArgResult->getTypeOnScope($scope->toMutatingScope(), $scope->toMutatingScope()->nativeTypesPromoted) : $scope->getType($callArg->value);
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

					$unsealedTypes = $constantArray->getUnsealedTypes();
					if ($unsealedTypes !== null) {
						$arrayTypeBuilder->makeUnsealed($unsealedTypes[0], $unsealedTypes[1]);
					}
				}

				$constantArray = $arrayTypeBuilder->getArray();

				if ($constantArray->isConstantArray()->yes() && $nonConstantArrayWasUnpacked) {
					$constantArrays = $constantArray->getConstantArrays();
					if ($constantArray->isList()->yes()) {
						// A list can't preserve precise indices when an
						// unknown number of values is prepended/appended —
						// every index would be shifted by an unknown
						// amount. Degrade to a `non-empty-list<...>` of
						// the value union.
						$array = new ArrayType($constantArray->generalize(GeneralizePrecision::lessSpecific())->getIterableKeyType(), $constantArray->getIterableValueType());
						$constantArray = $constantArray->isIterableAtLeastOnce()->yes()
							? new IntersectionType([$array, new NonEmptyArrayType()])
							: $array;
						$constantArray = TypeCombinator::intersect($constantArray, new AccessoryArrayListType());
					} elseif (count($constantArrays) === 1) {
						// Associative input — string keys keep their
						// precise values and the unknown count of
						// unpacked items lives in an unsealed `int` slot
						// of the result. Drops the auto-indexed
						// representatives that the unpacked-arg loop
						// inserted (they stand in for "0..N-1 of the
						// unpack value type" and are now subsumed by the
						// unsealed slot).
						$builder = ConstantArrayTypeBuilder::createEmpty();
						$intValues = [];
						foreach ($constantArrays[0]->getKeyTypes() as $i => $keyType) {
							$valueType = $constantArrays[0]->getValueTypes()[$i];
							if ($keyType->isString()->yes()) {
								$builder->setOffsetValueType($keyType, $valueType, $constantArrays[0]->isOptionalKey($i));
								continue;
							}
							$intValues[] = $valueType;
						}

						$unsealedKey = new IntegerType();
						$unsealedValue = count($intValues) > 0 ? TypeCombinator::union(...$intValues) : new MixedType();
						if ($constantArrays[0]->isUnsealed()->yes()) {
							$existing = $constantArrays[0]->getUnsealedTypes();
							if ($existing !== null) {
								$unsealedKey = TypeCombinator::union($unsealedKey, $existing[0]);
								$unsealedValue = TypeCombinator::union($unsealedValue, $existing[1]);
							}
						}
						$builder->makeUnsealed($unsealedKey, $unsealedValue);
						$constantArray = $builder->getArray();
					}
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

	/**
	 * The call-expression type is derived from $preResolvedAcceptor - the acceptor
	 * processArgs() selected from the arg types gathered on the arg-to-arg evolving
	 * scope (type-driven, generics resolved). When null (native-types-promoted, or
	 * a callable callee whose name was processed elsewhere), it falls back to a
	 * structural acceptor combined from the variants - generic resolution from the
	 * actual arg types lives in $preResolvedAcceptor, recomputed by on-demand /
	 * synthetic pricing that re-runs processArgs().
	 *
	 * @param FuncCall $expr
	 */
	private function resolveReturnType(NodeScopeResolver $nodeScopeResolver, MutatingScope $reflectionScope, bool $nativeTypesPromoted, Expr $expr, ?ExpressionResult $nameResult, ?ParametersAcceptor $preResolvedAcceptor, ArgsResult $argsResult): Type
	{
		// the operands/arguments were processed during processExpr; read their
		// already computed results instead of re-walking via Scope::getType().
		// The function reflection and dynamic-return-type extensions run on the
		// reflection scope (the lexical context / beforeScope). Synthetic nodes the
		// resolver builds (e.g. Clone_, call_user_func's inner FuncCall) are priced
		// on demand by the same helper.
		$getType = static function (Expr $e) use ($expr, $nameResult, $reflectionScope, $nodeScopeResolver, $argsResult, $nativeTypesPromoted): Type {
			if ($nameResult !== null && $e === $expr->name) {
				return $nativeTypesPromoted ? $nameResult->getNativeType() : $nameResult->getType();
			}

			$argResult = $argsResult->getArgResult($e);
			if ($argResult !== null) {
				return $nativeTypesPromoted ? $argResult->getNativeType() : $argResult->getType();
			}

			// Synthetic nodes (call_user_func's inner FuncCall, clone-with's Clone_)
			// have no captured arg result; they are priced on demand.
			$s = $nativeTypesPromoted ? $reflectionScope->doNotTreatPhpDocTypesAsCertain() : $reflectionScope;

			return $nodeScopeResolver->processSyntheticOnDemand($e, $s)->getTypeOnScope($s, $s->nativeTypesPromoted);
		};

		if ($expr->name instanceof Expr) {
			$calledOnType = $getType($expr->name);
			if ($calledOnType->isCallable()->no()) {
				return new ErrorType();
			}

			if ($preResolvedAcceptor !== null) {
				$parametersAcceptor = $preResolvedAcceptor;
			} else {
				$variants = $calledOnType->getCallableParametersAcceptors($reflectionScope);
				$parametersAcceptor = count($variants) === 1
					? $variants[0]
					: ParametersAcceptorSelector::combineAcceptors($variants);
			}

			$functionName = null;
			if ($expr->name instanceof String_) {
				/** @var non-empty-string $name */
				$name = $expr->name->value;
				$functionName = new Name($name);
			} elseif (
				$expr->name instanceof FuncCall
				&& $expr->name->name instanceof Name
				&& $expr->name->isFirstClassCallable()
			) {
				$functionName = $expr->name->name;
			}

			$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr);
			if ($normalizedNode !== null && $functionName !== null && $this->reflectionProvider->hasFunction($functionName, $reflectionScope)) {
				$functionReflection = $this->reflectionProvider->getFunction($functionName, $reflectionScope);
				$resolvedType = $this->getDynamicFunctionReturnType($reflectionScope, $normalizedNode, $functionReflection, $argsResult);
				if ($resolvedType !== null) {
					return $resolvedType;
				}
			}

			return $parametersAcceptor->getReturnType();
		}

		if (!$this->reflectionProvider->hasFunction($expr->name, $reflectionScope)) {
			return new ErrorType();
		}

		$functionReflection = $this->reflectionProvider->getFunction($expr->name, $reflectionScope);
		if ($nativeTypesPromoted) {
			return ParametersAcceptorSelector::combineAcceptors($functionReflection->getVariants())->getNativeReturnType();
		}

		if ($functionReflection->getName() === 'call_user_func') {
			$result = ArgumentsNormalizer::reorderCallUserFuncArguments($expr, $reflectionScope);
			if ($result !== null) {
				[, $innerFuncCall] = $result;

				return $getType($innerFuncCall);
			}
		}

		if ($functionReflection->getName() === 'call_user_func_array') {
			$result = ArgumentsNormalizer::reorderCallUserFuncArrayArguments($expr, $reflectionScope);
			if ($result !== null) {
				[, $innerFuncCall] = $result;

				return $getType($innerFuncCall);
			}
		}

		if ($preResolvedAcceptor !== null) {
			$parametersAcceptor = $preResolvedAcceptor;
		} else {
			$variants = $functionReflection->getVariants();
			$parametersAcceptor = count($variants) === 1
				? $variants[0]
				: ParametersAcceptorSelector::combineAcceptors($variants);
		}
		$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr);
		if ($normalizedNode !== null) {
			if ($functionReflection->getName() === 'clone' && count($normalizedNode->getArgs()) > 0) {
				$cloneType = $getType(new Expr\Clone_($normalizedNode->getArgs()[0]->value));
				if (count($normalizedNode->getArgs()) === 2) {
					$propertiesType = $getType($normalizedNode->getArgs()[1]->value);
					if ($propertiesType->isConstantArray()->yes()) {
						$constantArrays = $propertiesType->getConstantArrays();
						if (count($constantArrays) === 1) {
							$accessories = [];
							foreach ($constantArrays[0]->getKeyTypes() as $keyType) {
								$constantKeyTypes = $keyType->getConstantScalarValues();
								if (count($constantKeyTypes) !== 1) {
									return $cloneType;
								}
								$accessories[] = new HasPropertyType((string) $constantKeyTypes[0]);
							}
							if (count($accessories) > 0 && count($accessories) <= 16) {
								return TypeCombinator::intersect($cloneType, ...$accessories);
							}
						}
					}
				}

				return $cloneType;
			}
			$resolvedType = $this->getDynamicFunctionReturnType($reflectionScope, $normalizedNode, $functionReflection, $argsResult);
			if ($resolvedType !== null) {
				return $resolvedType;
			}
		}

		// the typeCallback keeps void; ExpressionResult projects void->null for
		// value reads, getKeepVoidType() keeps it
		return $parametersAcceptor->getReturnType();
	}

	/**
	 * Ported inside-out from the old TypeResolvingExprHandler::specifyTypes(): the
	 * FunctionTypeSpecifyingExtensions, conditional-return-type and @phpstan-assert
	 * narrowing are invoked on the already-processed argument results. The acceptor
	 * is $resolvedParametersAcceptor (type-driven, generics resolved by processArgs)
	 * rather than re-selected from the args on the asking scope. The subject's own
	 * default narrowing comes from DefaultNarrowingHelper instead of
	 * TypeSpecifier::handleDefaultTruthyOrFalseyContext(), which would re-enter this
	 * expression through TypeSpecifier::create().
	 *
	 * @param FuncCall $expr
	 */
	private function specifyTypes(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, Expr $expr, FuncCall $normalizedExpr, ?ExpressionResult $nameResult, ?ParametersAcceptor $resolvedParametersAcceptor, TypeSpecifierContext $context, ?ArgsResult $argsResult = null): SpecifiedTypes
	{
		if ($expr->name instanceof Name) {
			if ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				$args = $expr->getArgs();

				// runs lazily at narrowing-apply time - prime the storage with the
				// argument results, see MethodCallHandler::specifyTypes()
				$popPrimedStorage = $this->storagePrimer->pushPrimedStorage($scope, $args, $argsResult);
				try {
					foreach ($this->typeSpecifier->getFunctionTypeSpecifyingExtensions() as $extension) {
						if (!$extension->isFunctionSupported($functionReflection, $normalizedExpr, $context)) {
							continue;
						}

						return $extension->specifyTypes($functionReflection, $normalizedExpr, $scope, $context);
					}
				} finally {
					$popPrimedStorage();
				}

				if (count($args) > 0 && $resolvedParametersAcceptor !== null) {
					$specifiedTypes = $this->defaultNarrowingHelper->specifyTypesFromConditionalReturnType($context, $expr, $resolvedParametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$assertions = $functionReflection->getAsserts();
				if ($assertions->getAll() !== [] && $resolvedParametersAcceptor !== null) {
					$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
						$type,
						$resolvedParametersAcceptor->getResolvedTemplateTypeMap(),
						$resolvedParametersAcceptor instanceof ExtendedParametersAcceptor ? $resolvedParametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						TemplateTypeVariance::createInvariant(),
					));
					$specifiedTypes = $this->defaultNarrowingHelper->specifyTypesFromAsserts($context, $expr, $asserts, $resolvedParametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes
							->unionWith($this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context))
							->setRootExpr($specifiedTypes->getRootExpr());
					}
				}
			}

			return $this->defaultFuncCallNarrowing($nodeScopeResolver, $scope, $expr, $nameResult, $context);
		}

		$specifiedTypes = $this->specifyTypesFromCallableCall($nodeScopeResolver, $context, $expr, $nameResult, $resolvedParametersAcceptor, $scope);
		if ($specifiedTypes !== null) {
			return $specifiedTypes;
		}

		return $this->defaultFuncCallNarrowing($nodeScopeResolver, $scope, $expr, $nameResult, $context);
	}

	private function specifyTypesFromCallableCall(NodeScopeResolver $nodeScopeResolver, TypeSpecifierContext $context, FuncCall $call, ?ExpressionResult $nameResult, ?ParametersAcceptor $resolvedParametersAcceptor, MutatingScope $scope): ?SpecifiedTypes
	{
		if (!$call->name instanceof Expr) {
			return null;
		}

		if ($nameResult === null) {
			throw new ShouldNotHappenException();
		}

		$calleeType = $nameResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);

		$assertions = null;
		$parametersAcceptor = null;
		if ($calleeType->isCallable()->yes()) {
			if ($resolvedParametersAcceptor !== null) {
				$parametersAcceptor = $resolvedParametersAcceptor;
			} else {
				$variants = $calleeType->getCallableParametersAcceptors($scope);
				$parametersAcceptor = count($variants) === 1
					? $variants[0]
					: ParametersAcceptorSelector::combineAcceptors($variants);
			}
			if ($parametersAcceptor instanceof CallableParametersAcceptor) {
				$assertions = $parametersAcceptor->getAsserts();
			}
		}

		if ($assertions === null || $assertions->getAll() === []) {
			return null;
		}

		$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$parametersAcceptor->getResolvedTemplateTypeMap(),
			$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		));

		return $this->defaultNarrowingHelper->specifyTypesFromAsserts($context, $call, $asserts, $parametersAcceptor, $scope);
	}

	/**
	 * The default truthy/falsey narrowing of the call expression itself, gated by
	 * the same purity check TypeSpecifier::create() applies: a function with side
	 * effects (or an unknown / impure callee whose result is not remembered) is not
	 * narrowable - calling it twice may yield different values - so it contributes
	 * no entry. Mirrors create()'s FuncCall handling inside-out, without re-entering
	 * this expression through create().
	 *
	 */
	private function defaultFuncCallNarrowing(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, FuncCall $expr, ?ExpressionResult $nameResult, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$this->isFuncCallNarrowable($nodeScopeResolver, $scope, $expr, $nameResult)) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
	}

	private function isFuncCallNarrowable(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, FuncCall $expr, ?ExpressionResult $nameResult): bool
	{
		if ($expr->name instanceof Name) {
			if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
				// backwards compatibility with previous behaviour
				return false;
			}

			$hasSideEffects = $this->reflectionProvider->getFunction($expr->name, $scope)->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return false;
			}

			return $this->rememberPossiblyImpureFunctionValues || $hasSideEffects->no();
		}

		if ($nameResult === null) {
			throw new ShouldNotHappenException();
		}

		$nameType = $nameResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);
		if (!$nameType->isCallable()->yes()) {
			return true;
		}

		$isPure = null;
		foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
			$variantIsPure = $variant->isPure();
			$isPure = $isPure === null ? $variantIsPure : $isPure->and($variantIsPure);
		}

		if ($isPure === null) {
			return true;
		}

		if ($isPure->no()) {
			return false;
		}

		return $this->rememberPossiblyImpureFunctionValues || $isPure->yes();
	}

	private function getDynamicFunctionReturnType(MutatingScope $scope, FuncCall $normalizedNode, FunctionReflection $functionReflection, ArgsResult $argsResult): ?Type
	{
		$extensions = $this->dynamicReturnTypeExtensionRegistry->getDynamicFunctionReturnTypeExtensions($functionReflection);

		// re-expose the already-processed arguments so an extension's
		// Scope::getType($arg->value) reads the stored result instead of re-walking
		// the argument on demand (the call's argument storage frame is no longer
		// current when the return type is asked lazily)
		$popPrimedStorage = $this->storagePrimer->pushPrimedStorage($scope, $normalizedNode->getArgs(), $argsResult);
		try {
			foreach ($extensions as $dynamicFunctionReturnTypeExtension) {
				$resolvedType = $dynamicFunctionReturnTypeExtension->getTypeFromFunctionCall(
					$functionReflection,
					$normalizedNode,
					$scope,
				);

				if ($resolvedType !== null) {
					return $resolvedType;
				}
			}
		} finally {
			$popPrimedStorage();
		}

		return null;
	}

}
