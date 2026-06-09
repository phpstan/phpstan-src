<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Countable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\ClassConstFetch;
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
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function is_string;
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

		/** @var ExprHandler<Expr> $exprHandler */
		foreach ($this->container->getServicesByTag(ExprHandler::EXTENSION_TAG) as $exprHandler) {
			if (!$exprHandler->supports($expr)) {
				continue;
			}

			return $exprHandler->specifyTypes($this, $scope, $expr, $context);
		}

		return $this->specifyDefaultTypes($scope, $expr, $context);
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

	private function specifyTypesForConstantBinaryExpression(
		Expr $exprNode,
		Type $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		if (!$context->null() && $constantType->isFalse()->yes()) {
			$types = $this->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
			if (!$context->true() && ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			return $types->unionWith($this->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createFalse()->negate(),
			)->setRootExpr($rootExpr));
		}

		if (!$context->null() && $constantType->isTrue()->yes()) {
			$types = $this->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
			if (!$context->true() && ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			return $types->unionWith($this->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createTrue()->negate(),
			)->setRootExpr($rootExpr));
		}

		return null;
	}

	private function specifyTypesForConstantStringBinaryExpression(
		Expr $exprNode,
		Type $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		$scalarValues = $constantType->getConstantScalarValues();
		if (count($scalarValues) !== 1 || !is_string($scalarValues[0])) {
			return null;
		}
		$constantStringValue = $scalarValues[0];

		if (
			$exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& !$exprNode->isFirstClassCallable()
			&& strtolower($exprNode->name->toString()) === 'gettype'
			&& isset($exprNode->getArgs()[0])
		) {
			$type = null;
			if ($constantStringValue === 'string') {
				$type = new StringType();
			}
			if ($constantStringValue === 'array') {
				$type = new ArrayType(new MixedType(), new MixedType());
			}
			if ($constantStringValue === 'boolean') {
				$type = new BooleanType();
			}
			if (in_array($constantStringValue, ['resource', 'resource (closed)'], true)) {
				$type = new ResourceType();
			}
			if ($constantStringValue === 'integer') {
				$type = new IntegerType();
			}
			if ($constantStringValue === 'double') {
				$type = new FloatType();
			}
			if ($constantStringValue === 'NULL') {
				$type = new NullType();
			}
			if ($constantStringValue === 'object') {
				$type = new ObjectWithoutClassType();
			}

			if ($type !== null) {
				$callType = $this->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
				$argType = $this->create($exprNode->getArgs()[0]->value, $type, $context, $scope)->setRootExpr($rootExpr);
				return $callType->unionWith($argType);
			}
		}

		if (
			$context->true()
			&& $exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& !$exprNode->isFirstClassCallable()
			&& strtolower((string) $exprNode->name) === 'get_parent_class'
			&& isset($exprNode->getArgs()[0])
		) {
			$argType = $scope->getType($exprNode->getArgs()[0]->value);
			$objectType = new ObjectType($constantStringValue);
			$classStringType = new GenericClassStringType($objectType);

			if ($argType->isString()->yes()) {
				return $this->create(
					$exprNode->getArgs()[0]->value,
					$classStringType,
					$context,
					$scope,
				)->setRootExpr($rootExpr);
			}

			if ($argType->isObject()->yes()) {
				return $this->create(
					$exprNode->getArgs()[0]->value,
					$objectType,
					$context,
					$scope,
				)->setRootExpr($rootExpr);
			}

			return $this->create(
				$exprNode->getArgs()[0]->value,
				TypeCombinator::union($objectType, $classStringType),
				$context,
				$scope,
			)->setRootExpr($rootExpr);
		}

		if (
			$context->false()
			&& $exprNode instanceof FuncCall
			&& $exprNode->name instanceof Name
			&& !$exprNode->isFirstClassCallable()
			&& in_array(strtolower((string) $exprNode->name), [
				'trim', 'ltrim', 'rtrim', 'chop',
				'mb_trim', 'mb_ltrim', 'mb_rtrim',
			], true)
			&& isset($exprNode->getArgs()[0])
			&& $constantStringValue === ''
		) {
			$argValue = $exprNode->getArgs()[0]->value;
			$argType = $scope->getType($argValue);
			if ($argType->isString()->yes()) {
				return $this->create(
					$argValue,
					new IntersectionType([
						new StringType(),
						new AccessoryNonEmptyStringType(),
					]),
					$context->negate(),
					$scope,
				)->setRootExpr($rootExpr);
			}
		}

		return null;
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

	/** @internal */
	public function augmentDisjunctionTypes(
		MutatingScope $scope,
		MutatingScope $rightScope,
		SpecifiedTypes $leftNormalized,
		SpecifiedTypes $rightNormalized,
		Expr $leftExpr,
		Expr $rightExpr,
		bool $truthy,
		SpecifiedTypes $types,
	): SpecifiedTypes
	{
		$candidateExprs = [];
		foreach ($leftNormalized->getSureTypes() as $exprString => [$exprNode, $type]) {
			$candidateExprs[$exprString] = $exprNode;
		}
		foreach ($rightNormalized->getSureTypes() as $exprString => [$exprNode, $type]) {
			$candidateExprs[$exprString] = $exprNode;
		}

		$existingSureTypes = $types->getSureTypes();

		$viableCandidates = [];
		foreach ($candidateExprs as $exprString => $targetExpr) {
			if (isset($existingSureTypes[$exprString])) {
				continue;
			}
			if (!$scope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}
			$viableCandidates[$exprString] = $targetExpr;
		}

		if ($viableCandidates === []) {
			return $types;
		}

		if ($truthy) {
			$leftFilteredScope = $scope->filterByTruthyValue($leftExpr);
			$rightFilteredScope = $rightScope->filterByTruthyValue($rightExpr);
		} else {
			$leftFilteredScope = $scope->filterByFalseyValue($leftExpr);
			$rightFilteredScope = $rightScope->filterByFalseyValue($rightExpr);
		}

		foreach ($viableCandidates as $targetExpr) {
			if (!$leftFilteredScope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}
			if (!$rightFilteredScope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}

			$originalType = $scope->getType($targetExpr);
			$leftType = $leftFilteredScope->getType($targetExpr);
			$rightType = $rightFilteredScope->getType($targetExpr);

			if ($leftType->equals($originalType) || !$originalType->isSuperTypeOf($leftType)->yes()) {
				continue;
			}

			if ($rightType->equals($originalType) || !$originalType->isSuperTypeOf($rightType)->yes()) {
				continue;
			}

			$unionType = TypeCombinator::union($leftType, $rightType);
			if ($unionType->equals($originalType)) {
				continue;
			}

			$types = $types->unionWith(
				$this->create($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope),
			);
		}

		return $types;
	}

	/**
	 * Combines several `processBooleanConditionalTypes()` results into one map.
	 *
	 * A plain `array_merge()` would be keyed by the target expression string and
	 * therefore let a later result overwrite an earlier one targeting the same
	 * expression, silently dropping a holder. Holders for the same expression are
	 * unioned by their key instead so all of them survive.
	 *
	 * @param list<array<string, ConditionalExpressionHolder[]>> $holderLists
	 * @return array<string, ConditionalExpressionHolder[]>
	 *
	 * @internal
	 */
	public function mergeConditionalHolders(array $holderLists): array
	{
		$result = [];
		foreach ($holderLists as $holders) {
			foreach ($holders as $exprString => $exprHolders) {
				foreach ($exprHolders as $key => $holder) {
					$result[$exprString][$key] = $holder;
				}
			}
		}

		return $result;
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 *
	 * @internal
	 */
	public function processBooleanConditionalTypes(Scope $scope, SpecifiedTypes $conditionSpecifiedTypes, SpecifiedTypes $holderSpecifiedTypes, bool $holdersFromSureTypes, bool $holderSideIsNegated, Scope $rightScope, ?Expr $holderSideExpr = null): array
	{
		// The condition side asserts that its sub-expression evaluates truthy.
		// When that sub-expression is itself a compound boolean (e.g. `$a && $b`),
		// the narrowings making it true are spread across both the sure and
		// sureNot lists of its specification. All of them are conjuncts of the
		// single "this side is true" condition, so they must be gathered together
		// into one condition set. Picking only one list would drop a conjunct and
		// let the resulting holder fire too eagerly.
		$conditionExpressionTypes = [];
		foreach ($conditionSpecifiedTypes->getSureTypes() as $exprString => [$expr, $type]) {
			if (!$this->isTrackableExpression($expr)) {
				continue;
			}

			$scopeType = $scope->getType($expr);
			$conditionType = TypeCombinator::remove($scopeType, $type);
			if ($scopeType->equals($conditionType)) {
				continue;
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes(
				$expr,
				$conditionType,
			);
		}
		foreach ($conditionSpecifiedTypes->getSureNotTypes() as $exprString => [$expr, $type]) {
			if (!$this->isTrackableExpression($expr)) {
				continue;
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes(
				$expr,
				TypeCombinator::intersect($scope->getType($expr), $type),
			);
		}

		if (count($conditionExpressionTypes) > 0) {
			$holders = [];
			$holderTypes = $holdersFromSureTypes ? $holderSpecifiedTypes->getSureTypes() : $holderSpecifiedTypes->getSureNotTypes();

			// A holder side that is itself a compound boolean cannot always be split
			// into independent per-expression holders. In the `BooleanAnd` false
			// context the holder asserts its side is false: when that side is a
			// conjunction (`$a && $b`), its negation is the disjunction `!$a || !$b`,
			// which has no per-expression narrowing — narrowing each conjunct
			// independently would drop a reachable value (e.g. `$a = false, $b = true`).
			// Symmetrically, in the `BooleanOr` true context the holder asserts its
			// side is true, and a disjunction side (`$a || $b`) is itself a disjunction.
			// Such a side is left whole rather than split into over-narrowing holders.
			if ($this->isUnsplittableCompoundHolderSide($holderSideExpr, $holderSideIsNegated)) {
				return [];
			}

			foreach ($holderTypes as $exprString => [$expr, $type]) {
				if (!$this->isTrackableExpression($expr)) {
					continue;
				}

				$conditions = $conditionExpressionTypes;
				foreach (array_keys($conditions) as $conditionExprString) {
					if ($conditionExprString !== $exprString) {
						continue;
					}
					unset($conditions[$conditionExprString]);
				}

				if (count($conditions) === 0) {
					continue;
				}

				$targetScope = $expr instanceof Expr\Variable ? $scope : $rightScope;
				$targetType = $targetScope->getType($expr);
				$holderType = $holdersFromSureTypes
					? TypeCombinator::intersect($targetType, $type)
					: TypeCombinator::remove($targetType, $type);

				// These boolean-decomposition holders only refine an expression's
				// type in a future scope; they must never collapse it to never and
				// thereby mark the whole scope unreachable. A never result is an
				// artifact (e.g. removing a non-nullable property's full type after
				// swapping isset() narrowing), not a real contradiction.
				if ($holderType instanceof NeverType && !$targetType instanceof NeverType) {
					continue;
				}
				$holder = new ConditionalExpressionHolder(
					$conditions,
					ExpressionTypeHolder::createYes($expr, $holderType),
				);
				$holders[$exprString] ??= [];
				$holders[$exprString][$holder->getKey()] = $holder;
			}

			return $holders;
		}

		return [];
	}

	/**
	 * A holder side whose truth value is asserted as a disjunction cannot be
	 * decomposed into independent per-expression holders. That happens for a
	 * conjunction (`&&`) asserted false (negated context) and for a disjunction
	 * (`||`) asserted true.
	 */
	private function isUnsplittableCompoundHolderSide(?Expr $holderSideExpr, bool $holderSideIsNegated): bool
	{
		if ($holderSideExpr === null) {
			return false;
		}

		if ($holderSideIsNegated) {
			return $holderSideExpr instanceof BooleanAnd || $holderSideExpr instanceof LogicalAnd;
		}

		return $holderSideExpr instanceof BooleanOr || $holderSideExpr instanceof LogicalOr;
	}

	private function isTrackableExpression(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable) {
			return is_string($expr->name);
		}

		return $expr instanceof Expr\PropertyFetch
			|| $expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\StaticPropertyFetch;
	}

	/**
	 * @return array{Expr, ConstantScalarType, Type}|null
	 */
	private function findTypeExpressionsFromBinaryOperation(Scope $scope, Node\Expr\BinaryOp $binaryOperation): ?array
	{
		$leftType = $scope->getType($binaryOperation->left);
		$rightType = $scope->getType($binaryOperation->right);

		$rightExpr = $binaryOperation->right;
		if ($rightExpr instanceof AlwaysRememberedExpr) {
			$rightExpr = $rightExpr->getExpr();
		}

		$leftExpr = $binaryOperation->left;
		if ($leftExpr instanceof AlwaysRememberedExpr) {
			$leftExpr = $leftExpr->getExpr();
		}

		if (
			$leftType instanceof ConstantScalarType
			&& !$rightExpr instanceof ConstFetch
		) {
			return [$binaryOperation->right, $leftType, $rightType];
		} elseif (
			$rightType instanceof ConstantScalarType
			&& !$leftExpr instanceof ConstFetch
		) {
			return [$binaryOperation->left, $rightType, $leftType];
		}

		return null;
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

	public function resolveEqual(Expr\BinaryOp\Equal $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
		if ($expressions !== null) {
			$exprNode = $expressions[0];
			$constantType = $expressions[1];
			$otherType = $expressions[2];

			if (!$context->null() && $constantType->getValue() === null) {
				$trueTypes = [
					new NullType(),
					new ConstantBooleanType(false),
					new ConstantIntegerType(0),
					new ConstantFloatType(0.0),
					new ConstantStringType(''),
					new ConstantArrayType([], []),
				];
				return $this->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === false) {
				return $this->specifyTypesInCondition(
					$scope,
					$exprNode,
					$context->true() ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createFalsey()->negate(),
				)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === true) {
				return $this->specifyTypesInCondition(
					$scope,
					$exprNode,
					$context->true() ? TypeSpecifierContext::createTruthy() : TypeSpecifierContext::createTruthy()->negate(),
				)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === 0 && !$otherType->isInteger()->yes() && !$otherType->isBoolean()->yes()) {
				/* There is a difference between php 7.x and 8.x on the equality
				 * behavior between zero and the empty string, so to be conservative
				 * we leave it untouched regardless of the language version */
				if ($context->true()) {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantFloatType(0.0),
						new StringType(),
					];
				} else {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantFloatType(0.0),
						new ConstantStringType('0'),
					];
				}
				return $this->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === '') {
				/* There is a difference between php 7.x and 8.x on the equality
				 * behavior between zero and the empty string, so to be conservative
				 * we leave it untouched regardless of the language version */
				if ($context->true()) {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantIntegerType(0),
						new ConstantFloatType(0.0),
						new ConstantStringType(''),
					];
				} else {
					$trueTypes = [
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantStringType(''),
					];
				}
				return $this->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (
				$exprNode instanceof FuncCall
				&& $exprNode->name instanceof Name
				&& !$exprNode->isFirstClassCallable()
				&& in_array(strtolower($exprNode->name->toString()), ['gettype', 'get_class', 'get_debug_type'], true)
				&& isset($exprNode->getArgs()[0])
				&& $constantType->isString()->yes()
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}

			if (
				$context->true()
				&& $exprNode instanceof FuncCall
				&& $exprNode->name instanceof Name
				&& $exprNode->name->toLowerString() === 'preg_match'
				&& (new ConstantIntegerType(1))->isSuperTypeOf($constantType)->yes()
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}

			if (
				$context->true()
				&& $exprNode instanceof ClassConstFetch
				&& $exprNode->name instanceof Node\Identifier
				&& strtolower($exprNode->name->toString()) === 'class'
				&& $constantType->isString()->yes()
			) {
				return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}
		}

		$leftType = $scope->getType($expr->left);
		$rightType = $scope->getType($expr->right);

		$leftBooleanType = $leftType->toBoolean();
		if ($leftBooleanType instanceof ConstantBooleanType && $rightType->isBoolean()->yes()) {
			return $this->specifyTypesInCondition(
				$scope,
				new Expr\BinaryOp\Identical(
					new ConstFetch(new Name($leftBooleanType->getValue() ? 'true' : 'false')),
					$expr->right,
				),
				$context,
			)->setRootExpr($expr);
		}

		$rightBooleanType = $rightType->toBoolean();
		if ($rightBooleanType instanceof ConstantBooleanType && $leftType->isBoolean()->yes()) {
			return $this->specifyTypesInCondition(
				$scope,
				new Expr\BinaryOp\Identical(
					$expr->left,
					new ConstFetch(new Name($rightBooleanType->getValue() ? 'true' : 'false')),
				),
				$context,
			)->setRootExpr($expr);
		}

		if (
			!$context->null()
			&& $rightType->isArray()->yes()
			&& $leftType->isConstantArray()->yes() && $leftType->isIterableAtLeastOnce()->no()
		) {
			return $this->create($expr->right, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
		}

		if (
			!$context->null()
			&& $leftType->isArray()->yes()
			&& $rightType->isConstantArray()->yes() && $rightType->isIterableAtLeastOnce()->no()
		) {
			return $this->create($expr->left, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
		}

		if (
			($leftType->isString()->yes() && $rightType->isString()->yes())
			|| ($leftType->isInteger()->yes() && $rightType->isInteger()->yes())
			|| ($leftType->isFloat()->yes() && $rightType->isFloat()->yes())
			|| ($leftType->isEnum()->yes() && $rightType->isEnum()->yes())
		) {
			return $this->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
		}

		$leftExprString = $this->exprPrinter->printExpr($expr->left);
		$rightExprString = $this->exprPrinter->printExpr($expr->right);
		if ($leftExprString === $rightExprString) {
			if (!$expr->left instanceof Expr\Variable || !$expr->right instanceof Expr\Variable) {
				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			}
		}

		$leftTypes = $this->create($expr->left, $leftType, $context, $scope)->setRootExpr($expr);
		$rightTypes = $this->create($expr->right, $rightType, $context, $scope)->setRootExpr($expr);

		return $context->true()
			? $leftTypes->unionWith($rightTypes)
			: $leftTypes->normalize($scope)->intersectWith($rightTypes->normalize($scope));
	}

	public function resolveIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$leftExpr = $expr->left;
		$rightExpr = $expr->right;

		// Normalize to: fn() === expr
		if ($rightExpr instanceof FuncCall && !$leftExpr instanceof FuncCall) {
			$specifiedTypes = $this->resolveNormalizedIdentical(new Expr\BinaryOp\Identical(
				$rightExpr,
				$leftExpr,
			), $scope, $context);
		} else {
			$specifiedTypes = $this->resolveNormalizedIdentical(new Expr\BinaryOp\Identical(
				$leftExpr,
				$rightExpr,
			), $scope, $context);
		}

		// merge result of fn1() === fn2() and fn2() === fn1()
		if ($rightExpr instanceof FuncCall && $leftExpr instanceof FuncCall) {
			return $specifiedTypes->unionWith(
				$this->resolveNormalizedIdentical(new Expr\BinaryOp\Identical(
					$rightExpr,
					$leftExpr,
				), $scope, $context),
			);
		}

		return $specifiedTypes;
	}

	private function resolveNormalizedIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$leftExpr = $expr->left;
		$rightExpr = $expr->right;

		$unwrappedLeftExpr = $leftExpr;
		if ($leftExpr instanceof AlwaysRememberedExpr) {
			$unwrappedLeftExpr = $leftExpr->getExpr();
		}
		$unwrappedRightExpr = $rightExpr;
		if ($rightExpr instanceof AlwaysRememberedExpr) {
			$unwrappedRightExpr = $rightExpr->getExpr();
		}

		$rightType = $scope->getType($rightExpr);

		// (count($a) === $expr)
		if (
			!$context->null()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& count($unwrappedLeftExpr->getArgs()) >= 1
			&& $unwrappedLeftExpr->name instanceof Name
			&& in_array(strtolower((string) $unwrappedLeftExpr->name), ['count', 'sizeof'], true)
			&& $rightType->isInteger()->yes()
		) {
			// count($a) === count($b)
			if (
				$context->true()
				&& $unwrappedRightExpr instanceof FuncCall
				&& $unwrappedRightExpr->name instanceof Name
				&& !$unwrappedRightExpr->isFirstClassCallable()
				&& in_array($unwrappedRightExpr->name->toLowerString(), ['count', 'sizeof'], true)
				&& count($unwrappedRightExpr->getArgs()) >= 1
			) {
				$argType = $scope->getType($unwrappedRightExpr->getArgs()[0]->value);
				$sizeType = $scope->getType($leftExpr);

				$specifiedTypes = $this->specifyTypesForCountFuncCall($unwrappedRightExpr, $argType, $sizeType, $context, $scope, $expr);
				if ($specifiedTypes !== null) {
					return $specifiedTypes;
				}

				$leftArrayType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
				$rightArrayType = $scope->getType($unwrappedRightExpr->getArgs()[0]->value);
				if (
					$leftArrayType->isArray()->yes()
					&& $rightArrayType->isArray()->yes()
					&& !$rightType->isConstantScalarValue()->yes()
					&& ($leftArrayType->isIterableAtLeastOnce()->yes() || $rightArrayType->isIterableAtLeastOnce()->yes())
				) {
					$arrayTypes = $this->create($unwrappedLeftExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr);
					return $arrayTypes->unionWith(
						$this->create($unwrappedRightExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr),
					);
				}
			}

			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($rightType)->yes()) {
				return $this->create($unwrappedLeftExpr->getArgs()[0]->value, new NeverType(), $context, $scope)->setRootExpr($expr);
			}

			$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
			$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($rightType);
			if ($isZero->yes()) {
				$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);

				if ($context->truthy() && !$argType->isArray()->yes()) {
					$newArgType = new UnionType([
						new ObjectType(Countable::class),
						new ConstantArrayType([], []),
					]);
				} else {
					$newArgType = new ConstantArrayType([], []);
				}

				return $funcTypes->unionWith(
					$this->create($unwrappedLeftExpr->getArgs()[0]->value, $newArgType, $context, $scope)->setRootExpr($expr),
				);
			}

			$specifiedTypes = $this->specifyTypesForCountFuncCall($unwrappedLeftExpr, $argType, $rightType, $context, $scope, $expr);
			if ($specifiedTypes !== null) {
				if ($leftExpr !== $unwrappedLeftExpr) {
					$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
					return $specifiedTypes->unionWith($funcTypes);
				}
				return $specifiedTypes;
			}

			if ($context->truthy() && $argType->isArray()->yes()) {
				$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
				if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($rightType)->yes()) {
					return $funcTypes->unionWith(
						$this->create($unwrappedLeftExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr),
					);
				}

				return $funcTypes;
			}
		}

		// strlen($a) === $b
		if (
			!$context->null()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& in_array(strtolower((string) $unwrappedLeftExpr->name), ['strlen', 'mb_strlen'], true)
			&& count($unwrappedLeftExpr->getArgs()) === 1
			&& $rightType->isInteger()->yes()
		) {
			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($rightType)->yes()) {
				return $this->create($unwrappedLeftExpr->getArgs()[0]->value, new NeverType(), $context, $scope)->setRootExpr($expr);
			}

			$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($rightType);
			if ($isZero->yes()) {
				$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
				return $funcTypes->unionWith(
					$this->create($unwrappedLeftExpr->getArgs()[0]->value, new ConstantStringType(''), $context, $scope)->setRootExpr($expr),
				);
			}

			if ($context->truthy() && IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($rightType)->yes()) {
				$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
				if ($argType->isString()->yes()) {
					$funcTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);

					$accessory = new AccessoryNonEmptyStringType();
					if (IntegerRangeType::fromInterval(2, null)->isSuperTypeOf($rightType)->yes()) {
						$accessory = new AccessoryNonFalsyStringType();
					}
					$valueTypes = $this->create($unwrappedLeftExpr->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($expr);

					return $funcTypes->unionWith($valueTypes);
				}
			}
		}

		// array_key_first($a) !== null
		// array_key_last($a) !== null
		// array_find_key($a, $cb) !== null
		if (
			$unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& isset($unwrappedLeftExpr->getArgs()[0])
			&& $rightType->isNull()->yes()
		) {
			$funcName = $unwrappedLeftExpr->name->toLowerString();
			$bothDirections = in_array($funcName, ['array_key_first', 'array_key_last'], true);
			$notNullOnly = $funcName === 'array_find_key';
			if ($bothDirections || $notNullOnly) {
				$args = $unwrappedLeftExpr->getArgs();
				$argType = $scope->getType($args[0]->value);
				if ($argType->isArray()->yes()) {
					if ($bothDirections) {
						return $this->create($args[0]->value, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
					}
					if ($context->falsey()) {
						return $this->create($args[0]->value, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
					}
				}
			}
		}

		// preg_match($a) === $b
		if (
			$context->true()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& $unwrappedLeftExpr->name->toLowerString() === 'preg_match'
			&& (new ConstantIntegerType(1))->isSuperTypeOf($rightType)->yes()
		) {
			return $this->specifyTypesInCondition(
				$scope,
				$leftExpr,
				$context,
			)->setRootExpr($expr);
		}

		// get_class($a) === 'Foo'
		if (
			$context->true()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& in_array(strtolower($unwrappedLeftExpr->name->toString()), ['get_class', 'get_debug_type'], true)
			&& isset($unwrappedLeftExpr->getArgs()[0])
		) {
			$constantStringTypes = $rightType->getConstantStrings();
			if (count($constantStringTypes) === 1 && $this->reflectionProvider->hasClass($constantStringTypes[0]->getValue())) {
				return $this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					new ObjectType($constantStringTypes[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStringTypes[0]->getValue())->asFinal()),
					$context,
					$scope,
				)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
			if ($rightType->getClassStringObjectType()->isObject()->yes()) {
				return $this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					$rightType->getClassStringObjectType(),
					$context,
					$scope,
				)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
		}

		if (
			$context->truthy()
			&& $unwrappedLeftExpr instanceof FuncCall
			&& $unwrappedLeftExpr->name instanceof Name
			&& !$unwrappedLeftExpr->isFirstClassCallable()
			&& in_array(strtolower($unwrappedLeftExpr->name->toString()), [
				'substr', 'strstr', 'stristr', 'strchr', 'strrchr', 'strtolower', 'strtoupper', 'ucfirst', 'lcfirst',
				'mb_substr', 'mb_strstr', 'mb_stristr', 'mb_strchr', 'mb_strrchr', 'mb_strtolower', 'mb_strtoupper', 'mb_ucfirst', 'mb_lcfirst',
				'ucwords', 'mb_convert_case', 'mb_convert_kana',
			], true)
			&& isset($unwrappedLeftExpr->getArgs()[0])
			&& $rightType->isNonEmptyString()->yes()
		) {
			$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);

			if ($argType->isString()->yes()) {
				$specifiedTypes = new SpecifiedTypes();
				if (in_array(strtolower($unwrappedLeftExpr->name->toString()), ['strtolower', 'mb_strtolower'], true)) {
					$specifiedTypes = $this->create(
						$unwrappedRightExpr,
						TypeCombinator::intersect($rightType, new AccessoryLowercaseStringType()),
						$context,
						$scope,
					)->setRootExpr($expr);
				}
				if (in_array(strtolower($unwrappedLeftExpr->name->toString()), ['strtoupper', 'mb_strtoupper'], true)) {
					$specifiedTypes = $this->create(
						$unwrappedRightExpr,
						TypeCombinator::intersect($rightType, new AccessoryUppercaseStringType()),
						$context,
						$scope,
					)->setRootExpr($expr);
				}

				if ($rightType->isNonFalsyString()->yes()) {
					return $specifiedTypes->unionWith($this->create(
						$unwrappedLeftExpr->getArgs()[0]->value,
						TypeCombinator::intersect($argType, new AccessoryNonFalsyStringType()),
						$context,
						$scope,
					)->setRootExpr($expr));
				}

				return $specifiedTypes->unionWith($this->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					TypeCombinator::intersect($argType, new AccessoryNonEmptyStringType()),
					$context,
					$scope,
				)->setRootExpr($expr));
			}
		}

		if ($rightType->isString()->yes()) {
			$types = null;
			foreach ($rightType->getConstantStrings() as $constantString) {
				$specifiedType = $this->specifyTypesForConstantStringBinaryExpression($unwrappedLeftExpr, $constantString, $context, $scope, $expr);

				if ($specifiedType === null) {
					continue;
				}
				if ($types === null) {
					$types = $specifiedType;
					continue;
				}

				$types = $types->intersectWith($specifiedType);
			}

			if ($types !== null) {
				if ($leftExpr !== $unwrappedLeftExpr) {
					$types = $types->unionWith($this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr));
				}
				return $types;
			}
		}

		$expressions = $this->findTypeExpressionsFromBinaryOperation($scope, $expr);
		if ($expressions !== null) {
			$exprNode = $expressions[0];
			$constantType = $expressions[1];

			$unwrappedExprNode = $exprNode;
			if ($exprNode instanceof AlwaysRememberedExpr) {
				$unwrappedExprNode = $exprNode->getExpr();
			}

			$specifiedType = $this->specifyTypesForConstantBinaryExpression($unwrappedExprNode, $constantType, $context, $scope, $expr);
			if ($specifiedType !== null) {
				if ($exprNode !== $unwrappedExprNode) {
					$specifiedType = $specifiedType->unionWith(
						$this->create($exprNode, $constantType, $context, $scope)->setRootExpr($expr),
					);
				}
				return $specifiedType;
			}
		}

		// $a::class === 'Foo'
		if (
			$context->true() &&
			$unwrappedLeftExpr instanceof ClassConstFetch &&
			$unwrappedLeftExpr->class instanceof Expr &&
			$unwrappedLeftExpr->name instanceof Node\Identifier &&
			$unwrappedRightExpr instanceof ClassConstFetch &&
			strtolower($unwrappedLeftExpr->name->toString()) === 'class'
		) {
			$constantStrings = $rightType->getConstantStrings();
			if (count($constantStrings) === 1 && $constantStrings[0]->getValue() !== '') {
				if ($this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
					return $this->create(
						$unwrappedLeftExpr->class,
						new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
						$context,
						$scope,
					)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
				}
				return $this->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$unwrappedLeftExpr->class,
						new Name($constantStrings[0]->getValue()),
					),
					$context,
				)->unionWith($this->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
		}

		$leftType = $scope->getType($leftExpr);

		// 'Foo' === $a::class
		if (
			$context->true() &&
			$unwrappedRightExpr instanceof ClassConstFetch &&
			$unwrappedRightExpr->class instanceof Expr &&
			$unwrappedRightExpr->name instanceof Node\Identifier &&
			$unwrappedLeftExpr instanceof ClassConstFetch &&
			strtolower($unwrappedRightExpr->name->toString()) === 'class'
		) {
			$constantStrings = $leftType->getConstantStrings();
			if (count($constantStrings) === 1 && $constantStrings[0]->getValue() !== '') {
				if ($this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
					return $this->create(
						$unwrappedRightExpr->class,
						new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
						$context,
						$scope,
					)->unionWith($this->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr));
				}

				return $this->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$unwrappedRightExpr->class,
						new Name($constantStrings[0]->getValue()),
					),
					$context,
				)->unionWith($this->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr));
			}
		}

		if ($context->false()) {
			$identicalType = $scope->getType($expr);
			if ($identicalType instanceof ConstantBooleanType) {
				$never = new NeverType();
				$contextForTypes = $identicalType->getValue() ? $context->negate() : $context;
				if ($leftExpr instanceof AlwaysRememberedExpr) {
					$leftTypes = $this->create($unwrappedLeftExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				} else {
					$leftTypes = $this->create($leftExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				}
				if ($rightExpr instanceof AlwaysRememberedExpr) {
					$rightTypes = $this->create($unwrappedRightExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				} else {
					$rightTypes = $this->create($rightExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				}
				return $leftTypes->unionWith($rightTypes);
			}
		}

		$types = null;
		if (
			count($leftType->getFiniteTypes()) === 1
			|| (
				$context->true()
				&& $leftType->isConstantValue()->yes()
				&& !$rightType->equals($leftType)
				&& $rightType->isSuperTypeOf($leftType)->yes())
		) {
			$types = $this->create(
				$rightExpr,
				$leftType,
				$context,
				$scope,
			)->setRootExpr($expr);
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$types = $types->unionWith($this->create(
					$unwrappedRightExpr,
					$leftType,
					$context,
					$scope,
				))->setRootExpr($expr);
			}
		}
		if (
			count($rightType->getFiniteTypes()) === 1
			|| (
				$context->true()
				&& $rightType->isConstantValue()->yes()
				&& !$leftType->equals($rightType)
				&& $leftType->isSuperTypeOf($rightType)->yes()
			)
		) {
			$leftTypes = $this->create(
				$leftExpr,
				$rightType,
				$context,
				$scope,
			)->setRootExpr($expr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith($this->create(
					$unwrappedLeftExpr,
					$rightType,
					$context,
					$scope,
				))->setRootExpr($expr);
			}
			if ($types !== null) {
				$types = $types->unionWith($leftTypes);
			} else {
				$types = $leftTypes;
			}
		}

		if ($types !== null) {
			return $types;
		}

		$leftExprString = $this->exprPrinter->printExpr($unwrappedLeftExpr);
		$rightExprString = $this->exprPrinter->printExpr($unwrappedRightExpr);
		if ($leftExprString === $rightExprString) {
			if (!$unwrappedLeftExpr instanceof Expr\Variable || !$unwrappedRightExpr instanceof Expr\Variable) {
				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			}
		}

		if ($context->true()) {
			$leftTypes = $this->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
			$rightTypes = $this->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith(
					$this->create($unwrappedLeftExpr, $rightType, $context, $scope)->setRootExpr($expr),
				);
			}
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$rightTypes = $rightTypes->unionWith(
					$this->create($unwrappedRightExpr, $leftType, $context, $scope)->setRootExpr($expr),
				);
			}
			return $leftTypes->unionWith($rightTypes);
		} elseif ($context->false()) {
			return $this->create($leftExpr, $leftType, $context, $scope)->setRootExpr($expr)->normalize($scope)
				->intersectWith($this->create($rightExpr, $rightType, $context, $scope)->setRootExpr($expr)->normalize($scope));
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
