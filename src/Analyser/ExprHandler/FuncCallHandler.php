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
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\OutputBufferHelper;
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
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
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
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof FuncCall && !$expr->isFirstClassCallable();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
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

			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
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
				$arrayWalkOriginalArrayType = $scope->getType($arrayWalkArrayArg);
				$arrayWalkOriginalArrayNativeType = $scope->getNativeType($arrayWalkArrayArg);

				$nodeCallbackForArgs = static function (Node $node, Scope $scope) use ($nodeCallback, $callbackArg, $firstParamName, &$arrayWalkValueTypes): void {
					if ($node instanceof ClosureReturnStatementsNode && $node->getClosureExpr() === $callbackArg) {
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
						if (count($types) > 0) {
							$arrayWalkValueTypes = [
								TypeCombinator::union(...$types),
								TypeCombinator::union(...$nativeTypes),
							];
						}
					}
					$nodeCallback($node, $scope);
				};
			}
		}

		$scopeBeforeArgs = $scope;
		$argsResult = $nodeScopeResolver->processArgs($stmt, $functionReflection, null, $parametersAcceptor, $normalizedExpr, $scope, $storage, $nodeCallbackForArgs, $context);
		$scope = $argsResult->getScope();
		$hasYield = $argsResult->hasYield();
		$throwPoints = array_merge($throwPoints, $argsResult->getThrowPoints());
		$impurePoints = array_merge($impurePoints, $argsResult->getImpurePoints());
		$isAlwaysTerminating = $isAlwaysTerminating || $argsResult->isAlwaysTerminating();

		if ($arrayWalkValueTypes !== null && $arrayWalkArrayArg !== null) {
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
			$functionThrowPoint = $this->getFunctionThrowPoint($functionReflection, $parametersAcceptor, $normalizedExpr, $scope, $context);
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
					$this->getArrayFunctionAppendingType($functionReflection, $scopeBeforeArgs, $normalizedExpr),
					$this->getArrayFunctionAppendingType($functionReflection, $scopeBeforeArgs->doNotTreatPhpDocTypesAsCertain(), $normalizedExpr),
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
			$arrayArgType = $scope->getType($arrayArg);
			$arrayArgNativeType = $scope->getNativeType($arrayArg);

			$offsetType = $scopeBeforeArgs->getType($normalizedExpr->getArgs()[1]->value);

			if (isset($normalizedExpr->getArgs()[2])) {
				$lengthType = $scopeBeforeArgs->getType($normalizedExpr->getArgs()[2]->value);
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

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
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

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
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

		return null;
	}

}
