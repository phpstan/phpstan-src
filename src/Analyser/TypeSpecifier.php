<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function array_last;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function strtolower;
use function substr;
use const COUNT_NORMAL;

#[AutowiredService(name: 'typeSpecifier', factory: '@typeSpecifierFactory::create')]
final class TypeSpecifier
{

	/** @var MethodTypeSpecifyingExtension[][]|null */
	private ?array $methodTypeSpecifyingExtensionsByClass = null;

	/** @var StaticMethodTypeSpecifyingExtension[][]|null */
	private ?array $staticMethodTypeSpecifyingExtensionsByClass = null;

	private ?ExprHandlerRegistry $exprHandlerRegistry = null;

	/**
	 * @param FunctionTypeSpecifyingExtension[] $functionTypeSpecifyingExtensions
	 * @param MethodTypeSpecifyingExtension[] $methodTypeSpecifyingExtensions
	 * @param StaticMethodTypeSpecifyingExtension[] $staticMethodTypeSpecifyingExtensions
	 */
	public function __construct(
		private ExprPrinter $exprPrinter,
		private ReflectionProvider $reflectionProvider,
		private array $functionTypeSpecifyingExtensions,
		private array $methodTypeSpecifyingExtensions,
		private array $staticMethodTypeSpecifyingExtensions,
		private bool $rememberPossiblyImpureFunctionValues,
		private Container $container,
	)
	{
	}

	/**
	 * @api
	 */
	public function specifyTypesInCondition(
		Scope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		if ($expr instanceof Expr\CallLike && $expr->isFirstClassCallable()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		$exprHandler = $this->resolveExprHandler($expr);
		if ($exprHandler !== null) {
			return $exprHandler->specifyTypes($this, $scope, $expr, $context);
		}

		return $this->specifyDefaultTypes($scope, $expr, $context);
	}

	/**
	 * Resolves the ExprHandler for the given expression, memoizing the result by Expr class.
	 *
	 * @return ExprHandler<Expr>|null
	 */
	private function resolveExprHandler(Expr $expr): ?ExprHandler
	{
		return ($this->exprHandlerRegistry ??= $this->container->getByType(ExprHandlerRegistry::class))->resolve($expr);
	}

	/** @internal */
	public function isNormalCountCall(FuncCall $countFuncCall, Type $typeToCount, Scope $scope): TrinaryLogic
	{
		if (count($countFuncCall->getArgs()) === 1) {
			return TrinaryLogic::createYes();
		}

		$mode = $scope->getType($countFuncCall->getArgs()[1]->value);
		return (new ConstantIntegerType(COUNT_NORMAL))->isSuperTypeOf($mode)->result->or($typeToCount->getIterableValueType()->isArray()->negate());
	}

	/** @internal */
	public function specifyTypesForCountFuncCall(
		FuncCall $countFuncCall,
		Type $type,
		Type $sizeType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		$isConstantArray = $type->isConstantArray();
		$isList = $type->isList();
		$oneOrMore = IntegerRangeType::fromInterval(1, null);
		if (
			!$this->isNormalCountCall($countFuncCall, $type, $scope)->yes()
			|| (!$isConstantArray->yes() && !$isList->yes())
			|| !$oneOrMore->isSuperTypeOf($sizeType)->yes()
			|| $sizeType->isSuperTypeOf($type->getArraySize())->yes()
		) {
			return null;
		}

		if ($context->falsey() && $isConstantArray->yes()) {
			$remainingSize = TypeCombinator::remove($type->getArraySize(), $sizeType);
			if (!$remainingSize instanceof NeverType) {
				$negatedContext = $context->false()
					? TypeSpecifierContext::createTrue()
					: TypeSpecifierContext::createTruthy();
				$result = $this->specifyTypesForCountFuncCall(
					$countFuncCall,
					$type,
					$remainingSize,
					$negatedContext,
					$scope,
					$rootExpr,
				);
				if ($result !== null) {
					return $result;
				}
			}

			// Fallback: directly filter constant arrays by their exact sizes.
			// This avoids using TypeCombinator::remove() with falsey context,
			// which can incorrectly remove arrays whose count doesn't match
			// but whose shape is a subtype of the matched array.
			$keptTypes = [];
			foreach ($type->getConstantArrays() as $arrayType) {
				if ($sizeType->isSuperTypeOf($arrayType->getArraySize())->yes()) {
					continue;
				}

				$keptTypes[] = $arrayType;
			}
			if ($keptTypes !== []) {
				return $this->create(
					$countFuncCall->getArgs()[0]->value,
					TypeCombinator::union(...$keptTypes),
					$context->negate(),
					$scope,
				)->setRootExpr($rootExpr);
			}
		}

		$resultTypes = [];
		foreach ($type->getArrays() as $arrayType) {
			$isSizeSuperTypeOfArraySize = $sizeType->isSuperTypeOf($arrayType->getArraySize());
			if ($isSizeSuperTypeOfArraySize->no()) {
				continue;
			}

			if ($context->falsey() && $isSizeSuperTypeOfArraySize->maybe()) {
				continue;
			}

			$resultTypes[] = $isList->yes()
				? $arrayType->truncateListToSize($sizeType)
				: TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		if ($context->truthy() && $isConstantArray->yes() && $isList->yes()) {
			$hasOptionalKeysOrUnsealed = false;
			foreach ($type->getConstantArrays() as $arrayType) {
				if ($arrayType->getOptionalKeys() !== [] || $arrayType->isUnsealed()->yes()) {
					// Unsealed CATs can't be narrowed via the
					// `HasOffsetValueType`-only shortcut below — the
					// intersection of an unsealed shape with a single-slot
					// constraint produces `NeverType`. Fall through to
					// the full builder-based narrowing, which carries the
					// unsealed slot via the loop above.
					$hasOptionalKeysOrUnsealed = true;
					break;
				}
			}

			if (!$hasOptionalKeysOrUnsealed) {
				$argExpr = $countFuncCall->getArgs()[0]->value;
				$argExprString = $this->exprPrinter->printExpr($argExpr);

				$sizeMin = null;
				$sizeMax = null;
				if ($sizeType instanceof ConstantIntegerType) {
					$sizeMin = $sizeType->getValue();
					$sizeMax = $sizeType->getValue();
				} elseif ($sizeType instanceof IntegerRangeType) {
					$sizeMin = $sizeType->getMin();
					$sizeMax = $sizeType->getMax();
				}

				$sureTypes = [];
				$sureNotTypes = [];

				if ($sizeMin !== null && $sizeMin >= 1) {
					$sureTypes[$argExprString] = [$argExpr, new HasOffsetValueType(new ConstantIntegerType($sizeMin - 1), new MixedType())];
				}
				if ($sizeMax !== null) {
					$sureNotTypes[$argExprString] = [$argExpr, new HasOffsetValueType(new ConstantIntegerType($sizeMax), new MixedType())];
				}

				if ($sureTypes !== [] || $sureNotTypes !== []) {
					return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($rootExpr);
				}
			}
		}

		return $this->create($countFuncCall->getArgs()[0]->value, TypeCombinator::union(...$resultTypes), $context, $scope)->setRootExpr($rootExpr);
	}

	/**
	 * Fallback used by ExprHandler::specifyTypes implementations that have no
	 * Expr-specific narrowing: applies the default truthy/falsey narrowing, or
	 * returns empty SpecifiedTypes in a null context.
	 *
	 * @internal
	 */
	public function specifyDefaultTypes(Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$context->null()) {
			return $this->handleDefaultTruthyOrFalseyContext($context, $expr, $scope);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

	/** @internal */
	public function handleDefaultTruthyOrFalseyContext(TypeSpecifierContext $context, Expr $expr, Scope $scope): SpecifiedTypes
	{
		if ($context->null()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}
		if (!$context->truthy()) {
			$type = StaticTypeFactory::truthy();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr);
		} elseif (!$context->falsey()) {
			$type = StaticTypeFactory::falsey();
			return $this->create($expr, $type, TypeSpecifierContext::createFalse(), $scope)->setRootExpr($expr);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

	/** @internal */
	public function specifyTypesFromConditionalReturnType(
		TypeSpecifierContext $context,
		Expr\CallLike $call,
		ParametersAcceptor $parametersAcceptor,
		Scope $scope,
	): ?SpecifiedTypes
	{
		if (!$parametersAcceptor instanceof ResolvedFunctionVariant) {
			return null;
		}

		$returnType = $parametersAcceptor->getOriginalParametersAcceptor()->getReturnType();
		if (!$returnType instanceof ConditionalTypeForParameter) {
			return null;
		}

		if ($context->true()) {
			$leftType = new ConstantBooleanType(true);
			$rightType = new ConstantBooleanType(false);
		} elseif ($context->false()) {
			$leftType = new ConstantBooleanType(false);
			$rightType = new ConstantBooleanType(true);
		} elseif ($context->null()) {
			$leftType = new MixedType();
			$rightType = new NeverType();
		} else {
			return null;
		}

		$argumentExpr = null;
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				continue;
			}

			if ($arg->name !== null) {
				$paramName = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$paramName = $parameters[$i]->getName();
			} else {
				continue;
			}

			if ($returnType->getParameterName() !== '$' . $paramName) {
				continue;
			}

			$argumentExpr = $arg->value;
		}

		if ($argumentExpr === null) {
			return null;
		}

		return $this->getConditionalSpecifiedTypes($returnType, $leftType, $rightType, $scope, $argumentExpr);
	}

	private function getConditionalSpecifiedTypes(
		ConditionalTypeForParameter $conditionalType,
		Type $leftType,
		Type $rightType,
		Scope $scope,
		Expr $argumentExpr,
	): ?SpecifiedTypes
	{
		$targetType = $conditionalType->getTarget();
		$ifType = $conditionalType->getIf();
		$elseType = $conditionalType->getElse();

		if (
			(
				$argumentExpr instanceof Node\Scalar
				|| ($argumentExpr instanceof ConstFetch && in_array(strtolower($argumentExpr->name->toString()), ['true', 'false', 'null'], true))
			) && ($ifType instanceof NeverType || $elseType instanceof NeverType)
		) {
			return null;
		}

		if ($leftType->isSuperTypeOf($ifType)->yes() && $rightType->isSuperTypeOf($elseType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue();
		} elseif ($leftType->isSuperTypeOf($elseType)->yes() && $rightType->isSuperTypeOf($ifType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
		} else {
			return null;
		}

		$specifiedTypes = $this->create(
			$argumentExpr,
			$targetType,
			$context,
			$scope,
		);

		if ($targetType instanceof ConstantBooleanType) {
			if (!$targetType->getValue()) {
				$context = $context->negate();
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->specifyTypesInCondition($scope, $argumentExpr, $context));
		}

		return $specifiedTypes;
	}

	/** @internal */
	public function specifyTypesFromAsserts(TypeSpecifierContext $context, Expr\CallLike $call, Assertions $assertions, ParametersAcceptor $parametersAcceptor, Scope $scope): ?SpecifiedTypes
	{
		if ($context->null()) {
			$asserts = $assertions->getAsserts();
		} elseif ($context->true()) {
			$asserts = $assertions->getAssertsIfTrue();
		} elseif ($context->false()) {
			$asserts = $assertions->getAssertsIfFalse();
		} else {
			throw new ShouldNotHappenException();
		}

		if (count($asserts) === 0) {
			return null;
		}

		$argsMap = [];
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				continue;
			}

			if ($arg->name !== null) {
				$paramName = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$paramName = $parameters[$i]->getName();
			} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
				$lastParameter = array_last($parameters);
				$paramName = $lastParameter->getName();
			} else {
				continue;
			}

			$argsMap[$paramName][] = $arg->value;
		}
		foreach ($parameters as $parameter) {
			$name = $parameter->getName();
			$defaultValue = $parameter->getDefaultValue();
			if (isset($argsMap[$name]) || $defaultValue === null) {
				continue;
			}
			$argsMap[$name][] = new TypeExpr($defaultValue);
		}

		if ($call instanceof MethodCall) {
			$argsMap['this'] = [$call->var];
		}

		/** @var SpecifiedTypes|null $types */
		$types = null;

		foreach ($asserts as $assert) {
			foreach ($argsMap[substr($assert->getParameter()->getParameterName(), 1)] ?? [] as $parameterExpr) {
				$assertedType = TypeTraverser::map($assert->getType(), static function (Type $type, callable $traverse) use ($argsMap, $scope): Type {
					if ($type instanceof ConditionalTypeForParameter) {
						$parameterName = substr($type->getParameterName(), 1);
						if (array_key_exists($parameterName, $argsMap)) {
							$type = $traverse($type);
							if ($type instanceof ConditionalTypeForParameter) {
								$argType = TypeCombinator::union(...array_map(static fn (Expr $expr) => $scope->getType($expr), $argsMap[substr($type->getParameterName(), 1)]));
								return $type->toConditional($argType);
							}
							return $type;
						}
					}

					return $traverse($type);
				});

				$assertExpr = $assert->getParameter()->getExpr($parameterExpr);

				$templateTypeMap = $parametersAcceptor->getResolvedTemplateTypeMap();
				$containsUnresolvedTemplate = false;
				TypeTraverser::map(
					$assert->getOriginalType(),
					static function (Type $type, callable $traverse) use ($templateTypeMap, &$containsUnresolvedTemplate) {
						if ($type instanceof TemplateType && $type->getScope()->getClassName() !== null) {
							$resolvedType = $templateTypeMap->getType($type->getName());
							if ($resolvedType === null || $type->getBound()->equals($resolvedType)) {
								$containsUnresolvedTemplate = true;
								return $type;
							}
						}

						return $traverse($type);
					},
				);

				$newTypes = $this->create(
					$assertExpr,
					$assertedType,
					$assert->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue(),
					$scope,
				)->setRootExpr($containsUnresolvedTemplate || $assert->isEquality() ? $call : null);
				$types = $types !== null ? $types->unionWith($newTypes) : $newTypes;

				if (!$context->null() || !$assertedType instanceof ConstantBooleanType) {
					continue;
				}

				$subContext = $assertedType->getValue() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
				if ($assert->isNegated()) {
					$subContext = $subContext->negate();
				}

				$types = $types->unionWith($this->specifyTypesInCondition(
					$scope,
					$assertExpr,
					$subContext,
				));
			}
		}

		return $types;
	}

	/**
	 * @api
	 */
	public function create(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		if ($expr instanceof Instanceof_ || $expr instanceof Expr\List_) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		$specifiedExprs = [];
		if ($expr instanceof AlwaysRememberedExpr) {
			$specifiedExprs[] = $expr;
			$expr = $expr->expr;
		}

		if ($expr instanceof Expr\Assign) {
			$specifiedExprs[] = $expr->var;
			$specifiedExprs[] = $expr->expr;

			while ($expr->expr instanceof Expr\Assign) {
				$specifiedExprs[] = $expr->expr->var;
				$expr = $expr->expr;
			}
		} elseif ($expr instanceof Expr\AssignOp\Coalesce) {
			$specifiedExprs[] = $expr->var;
		} else {
			$specifiedExprs[] = $expr;
		}

		$types = null;

		foreach ($specifiedExprs as $specifiedExpr) {
			$newTypes = $this->createForExpr($specifiedExpr, $type, $context, $scope);

			if ($types === null) {
				$types = $newTypes;
			} else {
				$types = $types->unionWith($newTypes);
			}
		}

		return $types;
	}

	private function createForExpr(
		Expr $expr,
		Type $type,
		TypeSpecifierContext $context,
		Scope $scope,
	): SpecifiedTypes
	{
		if ($context->true()) {
			$containsNull = !$type->isNull()->no() && !$scope->getType($expr)->isNull()->no();
		} elseif ($context->false()) {
			$containsNull = !TypeCombinator::containsNull($type) && !$scope->getType($expr)->isNull()->no();
		}

		$originalExpr = $expr;
		if (isset($containsNull) && !$containsNull) {
			$expr = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($expr);
		}

		if (
			!$context->null()
			&& $expr instanceof Expr\BinaryOp\Coalesce
		) {
			if (
				($context->true() && $type->isSuperTypeOf($scope->getType($expr->right))->no())
				|| ($context->false() && $type->isSuperTypeOf($scope->getType($expr->right))->yes())
			) {
				$expr = $expr->left;
			}
		}

		if (
			$expr instanceof FuncCall
			&& $expr->name instanceof Name
		) {
			$has = $this->reflectionProvider->hasFunction($expr->name, $scope);
			if (!$has) {
				// backwards compatibility with previous behaviour
				return new SpecifiedTypes([], []);
			}

			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$hasSideEffects = $functionReflection->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return new SpecifiedTypes([], []);
			}

			if (!$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no()) {
				return new SpecifiedTypes([], []);
			}
		}

		if (
			$expr instanceof FuncCall
			&& !$expr->name instanceof Name
		) {
			$nameType = $scope->getType($expr->name);
			if ($nameType->isCallable()->yes()) {
				$isPure = null;
				foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
					$variantIsPure = $variant->isPure();
					$isPure = $isPure === null ? $variantIsPure : $isPure->and($variantIsPure);
				}

				if ($isPure !== null) {
					if ($isPure->no()) {
						return new SpecifiedTypes([], []);
					}

					if (!$this->rememberPossiblyImpureFunctionValues && !$isPure->yes()) {
						return new SpecifiedTypes([], []);
					}
				}
			}
		}

		if (
			$expr instanceof MethodCall
			&& $expr->name instanceof Node\Identifier
		) {
			$methodName = $expr->name->toString();
			$calledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				if (isset($containsNull) && !$containsNull) {
					return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
				}

				return new SpecifiedTypes([], []);
			}
		}

		if (
			$expr instanceof StaticCall
			&& $expr->name instanceof Node\Identifier
		) {
			$methodName = $expr->name->toString();
			if ($expr->class instanceof Name) {
				$calledOnType = $scope->resolveTypeByName($expr->class);
			} else {
				$calledOnType = $scope->getType($expr->class);
			}

			$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				if (isset($containsNull) && !$containsNull) {
					return $this->createNullsafeTypes($originalExpr, $scope, $context, $type);
				}

				return new SpecifiedTypes([], []);
			}
		}

		$sureTypes = [];
		$sureNotTypes = [];
		if ($context->false()) {
			$exprString = $this->exprPrinter->printExpr($expr);
			$sureNotTypes[$exprString] = [$expr, $type];

			if ($expr !== $originalExpr) {
				$originalExprString = $this->exprPrinter->printExpr($originalExpr);
				$sureNotTypes[$originalExprString] = [$originalExpr, $type];
			}
		} elseif ($context->true()) {
			$exprString = $this->exprPrinter->printExpr($expr);
			$sureTypes[$exprString] = [$expr, $type];

			if ($expr !== $originalExpr) {
				$originalExprString = $this->exprPrinter->printExpr($originalExpr);
				$sureTypes[$originalExprString] = [$originalExpr, $type];
			}
		}

		$types = new SpecifiedTypes($sureTypes, $sureNotTypes);
		if (isset($containsNull) && !$containsNull) {
			return $this->createNullsafeTypes($originalExpr, $scope, $context, $type)->unionWith($types);
		}

		return $types;
	}

	private function createNullsafeTypes(Expr $expr, Scope $scope, TypeSpecifierContext $context, ?Type $type): SpecifiedTypes
	{
		if ($expr instanceof Expr\NullsafePropertyFetch) {
			if ($type !== null) {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), $type, $context, $scope);
			} else {
				$propertyFetchTypes = $this->create(new PropertyFetch($expr->var, $expr->name), new NullType(), TypeSpecifierContext::createFalse(), $scope);
			}

			return $propertyFetchTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $scope),
			);
		}

		if ($expr instanceof Expr\NullsafeMethodCall) {
			if ($type !== null) {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), $type, $context, $scope);
			} else {
				$methodCallTypes = $this->create(new MethodCall($expr->var, $expr->name, $expr->args), new NullType(), TypeSpecifierContext::createFalse(), $scope);
			}

			return $methodCallTypes->unionWith(
				$this->create($expr->var, new NullType(), TypeSpecifierContext::createFalse(), $scope),
			);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\MethodCall) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->createNullsafeTypes($expr->var, $scope, $context, null);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($expr->class, $scope, $context, null);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->createNullsafeTypes($expr->class, $scope, $context, null);
		}

		return new SpecifiedTypes([], []);
	}

	/**
	 * @return FunctionTypeSpecifyingExtension[]
	 *
	 * @internal
	 */
	public function getFunctionTypeSpecifyingExtensions(): array
	{
		return $this->functionTypeSpecifyingExtensions;
	}

	/**
	 * @return MethodTypeSpecifyingExtension[]
	 *
	 * @internal
	 */
	public function getMethodTypeSpecifyingExtensionsForClass(string $className): array
	{
		if ($this->methodTypeSpecifyingExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->methodTypeSpecifyingExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->methodTypeSpecifyingExtensionsByClass = $byClass;
		}
		return $this->getTypeSpecifyingExtensionsForType($this->methodTypeSpecifyingExtensionsByClass, $className);
	}

	/**
	 * @return StaticMethodTypeSpecifyingExtension[]
	 *
	 * @internal
	 */
	public function getStaticMethodTypeSpecifyingExtensionsForClass(string $className): array
	{
		if ($this->staticMethodTypeSpecifyingExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->staticMethodTypeSpecifyingExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->staticMethodTypeSpecifyingExtensionsByClass = $byClass;
		}
		return $this->getTypeSpecifyingExtensionsForType($this->staticMethodTypeSpecifyingExtensionsByClass, $className);
	}

	/**
	 * @param MethodTypeSpecifyingExtension[][]|StaticMethodTypeSpecifyingExtension[][] $extensions
	 * @return mixed[]
	 */
	private function getTypeSpecifyingExtensionsForType(array $extensions, string $className): array
	{
		$extensionsForClass = [[]];
		$class = $this->reflectionProvider->getClass($className);
		foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
			if (!isset($extensions[$extensionClassName])) {
				continue;
			}

			$extensionsForClass[] = $extensions[$extensionClassName];
		}

		return array_merge(...$extensionsForClass);
	}

}
