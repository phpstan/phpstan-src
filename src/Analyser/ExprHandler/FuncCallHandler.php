<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper;
use PHPStan\Analyser\ExprHandler\Helper\FuncCallScopeEffectsHelper;
use PHPStan\Analyser\ExprHandler\Helper\VoidToNullTypeTransformer;
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
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function is_string;

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
		private EarlyTerminatingCallHelper $earlyTerminatingCallHelper,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredExtensions(of: DynamicFunctionThrowTypeExtension::class)]
		private ExtensionsCollection $dynamicFunctionThrowTypeExtensions,
		private DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
		private FuncCallScopeEffectsHelper $funcCallScopeEffectsHelper,
		private ExpressionResultFactory $expressionResultFactory,
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
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		if ($expr->name instanceof Expr) {
			// process the dynamic callee name first, then consume its type rather
			// than reading it before processExprNode() stores its result
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
			$clonePropertiesArgType = $scope->getType($normalizedExpr->getArgs()[1]->value);
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
		$argsGatherer = null;
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
				$arrayWalkOriginalArrayType = $scope->getType($arrayWalkArrayArg);
				$arrayWalkOriginalArrayNativeType = $scope->getNativeType($arrayWalkArrayArg);

				$argsGatherer = static function (Node $node, Scope $scope) use ($callbackArg, $firstParamName, &$arrayWalkValueTypes): void {
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
				};
			}
		}

		$scopeBeforeArgs = $scope;
		if ($argsGatherer !== null) {
			$nodeScopeResolver->pushNodeGatherer($argsGatherer);
		}
		try {
			$argsResult = $nodeScopeResolver->processArgs($stmt, $functionReflection, null, $variants, $namedArgumentsVariants, $normalizedExpr, $scope, $storage, $nodeCallback, $context);
		} finally {
			if ($argsGatherer !== null) {
				$nodeScopeResolver->popNodeGatherer();
			}
		}
		$resolvedParametersAcceptor = $argsResult->getResolvedParametersAcceptor();
		$scope = $argsResult->getScope();
		$hasYield = $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		if ($functionReflection !== null) {
			// created after the args were processed - the side-effect flip
			// parameters (print_r's $return, ...) read an argument's type, which
			// is only available once the argument was processed
			$impurePoint = SimpleImpurePoint::createFromVariant($functionReflection, $parametersAcceptor, $scope, $expr->getArgs());
			if ($impurePoint !== null) {
				$impurePoints[] = new ImpurePoint($scopeBeforeArgs, $expr, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain());
			}
		}

		if ($arrayWalkValueTypes !== null && $arrayWalkArrayArg !== null) {
			$scope = $this->funcCallScopeEffectsHelper->applyArrayWalkResult($nodeScopeResolver, $stmt, $arrayWalkArrayArg, $arrayWalkValueTypes, $arrayWalkOriginalArrayType, $arrayWalkOriginalArrayNativeType, $scope, $storage, $nodeCallback);
		}

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
			// A conditional-return never (e.g. `($x is Foo ? never : string)`) only
			// resolves to never once the actual argument types are folded in by the
			// type-driven resolved acceptor.
			if ($resolvedParametersAcceptor !== null) {
				$resolvedReturnType = $resolvedParametersAcceptor->getReturnType();
				$isAlwaysTerminating = $isAlwaysTerminating || ($resolvedReturnType instanceof NeverType && $resolvedReturnType->isExplicit());
			}
			$functionThrowPoint = $this->getFunctionThrowPoint($functionReflection, $parametersAcceptor, $normalizedExpr, $scope, $context);
			if ($functionThrowPoint !== null) {
				$throwPoints[] = $functionThrowPoint;
			}
		} else {
			$throwPoints[] = InternalThrowPoint::createImplicit($scope, $expr);
		}

		$scope = $this->funcCallScopeEffectsHelper->applyCallScopeEffects($nodeScopeResolver, $stmt, $normalizedExpr, $functionReflection, $parametersAcceptor, $scope, $scopeBeforeArgs, $storage, $nodeCallback);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	private function getFunctionThrowPoint(
		FunctionReflection $functionReflection,
		?ParametersAcceptor $parametersAcceptor,
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
			$returnType = $scope->getType($normalizedFuncCall);
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
				$functionReturnedType = $scope->getType($normalizedFuncCall);
				if (!$context->isInThrow() || !(new ObjectType(Throwable::class))->isSuperTypeOf($functionReturnedType)->yes()) {
					return InternalThrowPoint::createImplicit($scope, $normalizedFuncCall);
				}
			}
		}

		return null;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if (
			$expr->name instanceof Name
			&& $this->earlyTerminatingCallHelper->isEarlyTerminatingFunctionCall($expr->name->toString())
		) {
			return new NeverType(true);
		}

		if ($expr->name instanceof Expr) {
			$calledOnType = $scope->getType($expr->name);
			if ($calledOnType->isCallable()->no()) {
				return new ErrorType();
			}

			$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$expr->getArgs(),
				$calledOnType->getCallableParametersAcceptors($scope),
				null,
			);

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
			if ($normalizedNode !== null && $functionName !== null && $this->reflectionProvider->hasFunction($functionName, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($functionName, $scope);
				$resolvedType = $this->getDynamicFunctionReturnType($scope, $normalizedNode, $functionReflection);
				if ($resolvedType !== null) {
					return $resolvedType;
				}
			}

			return $parametersAcceptor->getReturnType();
		}

		if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
			return new ErrorType();
		}

		$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
		if ($scope->nativeTypesPromoted) {
			return ParametersAcceptorSelector::combineAcceptors($functionReflection->getVariants())->getNativeReturnType();
		}

		if ($functionReflection->getName() === 'call_user_func') {
			$result = ArgumentsNormalizer::reorderCallUserFuncArguments($expr, $scope);
			if ($result !== null) {
				[, $innerFuncCall] = $result;

				return $scope->getType($innerFuncCall);
			}
		}

		if ($functionReflection->getName() === 'call_user_func_array') {
			$result = ArgumentsNormalizer::reorderCallUserFuncArrayArguments($expr, $scope);
			if ($result !== null) {
				[, $innerFuncCall] = $result;

				return $scope->getType($innerFuncCall);
			}
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$expr->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);
		$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr);
		if ($normalizedNode !== null) {
			if ($functionReflection->getName() === 'clone' && count($normalizedNode->getArgs()) > 0) {
				$cloneType = $scope->getType(new Expr\Clone_($normalizedNode->getArgs()[0]->value));
				if (count($normalizedNode->getArgs()) === 2) {
					$propertiesType = $scope->getType($normalizedNode->getArgs()[1]->value);
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
			$resolvedType = $this->getDynamicFunctionReturnType($scope, $normalizedNode, $functionReflection);
			if ($resolvedType !== null) {
				return $resolvedType;
			}
		}

		return VoidToNullTypeTransformer::transform($parametersAcceptor->getReturnType(), $expr);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($expr->name instanceof Name) {
			if ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
				// lazy create parametersAcceptor, as creation can be expensive
				$parametersAcceptor = null;

				$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
				$normalizedExpr = $expr;
				$args = $expr->getArgs();
				if (count($args) > 0) {
					$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants());
					$normalizedExpr = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $expr) ?? $expr;
				}

				foreach ($typeSpecifier->getFunctionTypeSpecifyingExtensions() as $extension) {
					if (!$extension->isFunctionSupported($functionReflection, $normalizedExpr, $context)) {
						continue;
					}

					return $extension->specifyTypes($functionReflection, $normalizedExpr, $scope, $context);
				}

				if (count($args) > 0) {
					$specifiedTypes = $typeSpecifier->specifyTypesFromConditionalReturnType($context, $expr, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes;
					}
				}

				$assertions = $functionReflection->getAsserts();
				if ($assertions->getAll() !== []) {
					$parametersAcceptor ??= ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants());

					$asserts = $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
						$type,
						$parametersAcceptor->getResolvedTemplateTypeMap(),
						$parametersAcceptor instanceof ExtendedParametersAcceptor ? $parametersAcceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						TemplateTypeVariance::createInvariant(),
					));
					$specifiedTypes = $typeSpecifier->specifyTypesFromAsserts($context, $expr, $asserts, $parametersAcceptor, $scope);
					if ($specifiedTypes !== null) {
						return $specifiedTypes
							->unionWith($typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope))
							->setRootExpr($specifiedTypes->getRootExpr());
					}
				}
			}

			return $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		}

		$specifiedTypes = $this->specifyTypesFromCallableCall($typeSpecifier, $context, $expr, $scope);
		if ($specifiedTypes !== null) {
			return $specifiedTypes;
		}

		return $typeSpecifier->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
	}

	private function specifyTypesFromCallableCall(TypeSpecifier $typeSpecifier, TypeSpecifierContext $context, FuncCall $call, Scope $scope): ?SpecifiedTypes
	{
		if (!$call->name instanceof Expr) {
			return null;
		}

		$calleeType = $scope->getType($call->name);

		$assertions = null;
		$parametersAcceptor = null;
		if ($calleeType->isCallable()->yes()) {
			$variants = $calleeType->getCallableParametersAcceptors($scope);
			$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $call->getArgs(), $variants);
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

		return $typeSpecifier->specifyTypesFromAsserts($context, $call, $asserts, $parametersAcceptor, $scope);
	}

	private function getDynamicFunctionReturnType(MutatingScope $scope, FuncCall $normalizedNode, FunctionReflection $functionReflection): ?Type
	{
		foreach ($this->dynamicReturnTypeExtensionRegistry->getDynamicFunctionReturnTypeExtensions($functionReflection) as $dynamicFunctionReturnTypeExtension) {
			$resolvedType = $dynamicFunctionReturnTypeExtension->getTypeFromFunctionCall(
				$functionReflection,
				$normalizedNode,
				$scope,
			);

			if ($resolvedType !== null) {
				return $resolvedType;
			}
		}

		// for always-true/always-false type checks the call's own narrowing
		// decides the return type - the verdict reads the same specified types
		// the check contributes when used as a condition
		if (
			$normalizedNode->name instanceof Name
			&& in_array($normalizedNode->name->toLowerString(), ['array_key_exists', 'key_exists', 'in_array', 'is_subclass_of'], true)
		) {
			$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $normalizedNode);
			if ($isAlways !== null) {
				return new ConstantBooleanType($isAlways);
			}
		}

		return null;
	}

}
