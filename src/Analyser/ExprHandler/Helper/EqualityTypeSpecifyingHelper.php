<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Countable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function in_array;
use function is_string;
use function strtolower;

/**
 * Specifies types narrowed by loose (`==`) and strict (`===`) equality
 * comparisons. Used by BinaryOpHandler.
 */
#[AutowiredService]
final class EqualityTypeSpecifyingHelper
{

	public function __construct(
		private TypeSpecifier $typeSpecifier,
		private ReflectionProvider $reflectionProvider,
		private ExprPrinter $exprPrinter,
	)
	{
	}

	public function specifyTypesForEqual(Expr\BinaryOp\Equal $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
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
				return $this->typeSpecifier->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === false) {
				return $this->typeSpecifier->specifyTypesInCondition(
					$scope,
					$exprNode,
					$context->true() ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createFalsey()->negate(),
				)->setRootExpr($expr);
			}

			if (!$context->null() && $constantType->getValue() === true) {
				return $this->typeSpecifier->specifyTypesInCondition(
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
				return $this->typeSpecifier->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
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
				return $this->typeSpecifier->create($exprNode, new UnionType($trueTypes), $context, $scope)->setRootExpr($expr);
			}

			if (
				$exprNode instanceof FuncCall
				&& $exprNode->name instanceof Name
				&& !$exprNode->isFirstClassCallable()
				&& in_array(strtolower($exprNode->name->toString()), ['gettype', 'get_class', 'get_debug_type'], true)
				&& isset($exprNode->getArgs()[0])
				&& $constantType->isString()->yes()
			) {
				return $this->typeSpecifier->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}

			if (
				$context->true()
				&& $exprNode instanceof FuncCall
				&& $exprNode->name instanceof Name
				&& $exprNode->name->toLowerString() === 'preg_match'
				&& (new ConstantIntegerType(1))->isSuperTypeOf($constantType)->yes()
			) {
				return $this->typeSpecifier->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}

			if (
				$context->true()
				&& $exprNode instanceof ClassConstFetch
				&& $exprNode->name instanceof Node\Identifier
				&& strtolower($exprNode->name->toString()) === 'class'
				&& $constantType->isString()->yes()
			) {
				return $this->typeSpecifier->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
			}
		}

		$leftType = $scope->getType($expr->left);
		$rightType = $scope->getType($expr->right);

		$leftBooleanType = $leftType->toBoolean();
		if ($leftBooleanType instanceof ConstantBooleanType && $rightType->isBoolean()->yes()) {
			return $this->typeSpecifier->specifyTypesInCondition(
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
			return $this->typeSpecifier->specifyTypesInCondition(
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
			return $this->typeSpecifier->create($expr->right, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
		}

		if (
			!$context->null()
			&& $leftType->isArray()->yes()
			&& $rightType->isConstantArray()->yes() && $rightType->isIterableAtLeastOnce()->no()
		) {
			return $this->typeSpecifier->create($expr->left, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
		}

		if (
			($leftType->isString()->yes() && $rightType->isString()->yes())
			|| ($leftType->isInteger()->yes() && $rightType->isInteger()->yes())
			|| ($leftType->isFloat()->yes() && $rightType->isFloat()->yes())
			|| ($leftType->isEnum()->yes() && $rightType->isEnum()->yes())
		) {
			return $this->typeSpecifier->specifyTypesInCondition($scope, new Expr\BinaryOp\Identical($expr->left, $expr->right), $context)->setRootExpr($expr);
		}

		$leftExprString = $this->exprPrinter->printExpr($expr->left);
		$rightExprString = $this->exprPrinter->printExpr($expr->right);
		if ($leftExprString === $rightExprString) {
			if (!$expr->left instanceof Expr\Variable || !$expr->right instanceof Expr\Variable) {
				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			}
		}

		$leftTypes = $this->typeSpecifier->create($expr->left, $leftType, $context, $scope)->setRootExpr($expr);
		$rightTypes = $this->typeSpecifier->create($expr->right, $rightType, $context, $scope)->setRootExpr($expr);

		return $context->true()
			? $leftTypes->unionWith($rightTypes)
			: $leftTypes->normalize($scope)->intersectWith($rightTypes->normalize($scope));
	}

	public function specifyTypesForIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$leftExpr = $expr->left;
		$rightExpr = $expr->right;

		// Normalize to: fn() === expr
		if ($rightExpr instanceof FuncCall && !$leftExpr instanceof FuncCall) {
			$specifiedTypes = $this->specifyTypesForNormalizedIdentical(new Expr\BinaryOp\Identical(
				$rightExpr,
				$leftExpr,
			), $scope, $context);
		} else {
			$specifiedTypes = $this->specifyTypesForNormalizedIdentical(new Expr\BinaryOp\Identical(
				$leftExpr,
				$rightExpr,
			), $scope, $context);
		}

		// merge result of fn1() === fn2() and fn2() === fn1()
		if ($rightExpr instanceof FuncCall && $leftExpr instanceof FuncCall) {
			return $specifiedTypes->unionWith(
				$this->specifyTypesForNormalizedIdentical(new Expr\BinaryOp\Identical(
					$rightExpr,
					$leftExpr,
				), $scope, $context),
			);
		}

		return $specifiedTypes;
	}

	private function specifyTypesForNormalizedIdentical(Expr\BinaryOp\Identical $expr, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
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

				$specifiedTypes = $this->typeSpecifier->specifyTypesForCountFuncCall($unwrappedRightExpr, $argType, $sizeType, $context, $scope, $expr);
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
					$arrayTypes = $this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr);
					return $arrayTypes->unionWith(
						$this->typeSpecifier->create($unwrappedRightExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr),
					);
				}
			}

			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($rightType)->yes()) {
				return $this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, new NeverType(), $context, $scope)->setRootExpr($expr);
			}

			$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
			$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($rightType);
			if ($isZero->yes()) {
				$funcTypes = $this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);

				if ($context->truthy() && !$argType->isArray()->yes()) {
					$newArgType = new UnionType([
						new ObjectType(Countable::class),
						new ConstantArrayType([], []),
					]);
				} else {
					$newArgType = new ConstantArrayType([], []);
				}

				return $funcTypes->unionWith(
					$this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, $newArgType, $context, $scope)->setRootExpr($expr),
				);
			}

			$specifiedTypes = $this->typeSpecifier->specifyTypesForCountFuncCall($unwrappedLeftExpr, $argType, $rightType, $context, $scope, $expr);
			if ($specifiedTypes !== null) {
				if ($leftExpr !== $unwrappedLeftExpr) {
					$funcTypes = $this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
					return $specifiedTypes->unionWith($funcTypes);
				}
				return $specifiedTypes;
			}

			if ($context->truthy() && $argType->isArray()->yes()) {
				$funcTypes = $this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
				if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($rightType)->yes()) {
					return $funcTypes->unionWith(
						$this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, new NonEmptyArrayType(), $context, $scope)->setRootExpr($expr),
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
				return $this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, new NeverType(), $context, $scope)->setRootExpr($expr);
			}

			$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($rightType);
			if ($isZero->yes()) {
				$funcTypes = $this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
				return $funcTypes->unionWith(
					$this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, new ConstantStringType(''), $context, $scope)->setRootExpr($expr),
				);
			}

			if ($context->truthy() && IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($rightType)->yes()) {
				$argType = $scope->getType($unwrappedLeftExpr->getArgs()[0]->value);
				if ($argType->isString()->yes()) {
					$funcTypes = $this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);

					$accessory = new AccessoryNonEmptyStringType();
					if (IntegerRangeType::fromInterval(2, null)->isSuperTypeOf($rightType)->yes()) {
						$accessory = new AccessoryNonFalsyStringType();
					}
					$valueTypes = $this->typeSpecifier->create($unwrappedLeftExpr->getArgs()[0]->value, $accessory, $context, $scope)->setRootExpr($expr);

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
						return $this->typeSpecifier->create($args[0]->value, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
					}
					if ($context->falsey()) {
						return $this->typeSpecifier->create($args[0]->value, new NonEmptyArrayType(), $context->negate(), $scope)->setRootExpr($expr);
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
			return $this->typeSpecifier->specifyTypesInCondition(
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
				return $this->typeSpecifier->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					new ObjectType($constantStringTypes[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStringTypes[0]->getValue())->asFinal()),
					$context,
					$scope,
				)->unionWith($this->typeSpecifier->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
			}
			if ($rightType->getClassStringObjectType()->isObject()->yes()) {
				return $this->typeSpecifier->create(
					$unwrappedLeftExpr->getArgs()[0]->value,
					$rightType->getClassStringObjectType(),
					$context,
					$scope,
				)->unionWith($this->typeSpecifier->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
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
					$specifiedTypes = $this->typeSpecifier->create(
						$unwrappedRightExpr,
						TypeCombinator::intersect($rightType, new AccessoryLowercaseStringType()),
						$context,
						$scope,
					)->setRootExpr($expr);
				}
				if (in_array(strtolower($unwrappedLeftExpr->name->toString()), ['strtoupper', 'mb_strtoupper'], true)) {
					$specifiedTypes = $this->typeSpecifier->create(
						$unwrappedRightExpr,
						TypeCombinator::intersect($rightType, new AccessoryUppercaseStringType()),
						$context,
						$scope,
					)->setRootExpr($expr);
				}

				if ($rightType->isNonFalsyString()->yes()) {
					return $specifiedTypes->unionWith($this->typeSpecifier->create(
						$unwrappedLeftExpr->getArgs()[0]->value,
						TypeCombinator::intersect($argType, new AccessoryNonFalsyStringType()),
						$context,
						$scope,
					)->setRootExpr($expr));
				}

				return $specifiedTypes->unionWith($this->typeSpecifier->create(
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
					$types = $types->unionWith($this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr));
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
						$this->typeSpecifier->create($exprNode, $constantType, $context, $scope)->setRootExpr($expr),
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
					return $this->typeSpecifier->create(
						$unwrappedLeftExpr->class,
						new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
						$context,
						$scope,
					)->unionWith($this->typeSpecifier->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
				}
				return $this->typeSpecifier->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$unwrappedLeftExpr->class,
						new Name($constantStrings[0]->getValue()),
					),
					$context,
				)->unionWith($this->typeSpecifier->create($leftExpr, $rightType, $context, $scope))->setRootExpr($expr);
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
					return $this->typeSpecifier->create(
						$unwrappedRightExpr->class,
						new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
						$context,
						$scope,
					)->unionWith($this->typeSpecifier->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr));
				}

				return $this->typeSpecifier->specifyTypesInCondition(
					$scope,
					new Instanceof_(
						$unwrappedRightExpr->class,
						new Name($constantStrings[0]->getValue()),
					),
					$context,
				)->unionWith($this->typeSpecifier->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr));
			}
		}

		if ($context->false()) {
			$identicalType = $scope->getType($expr);
			if ($identicalType instanceof ConstantBooleanType) {
				$never = new NeverType();
				$contextForTypes = $identicalType->getValue() ? $context->negate() : $context;
				if ($leftExpr instanceof AlwaysRememberedExpr) {
					$leftTypes = $this->typeSpecifier->create($unwrappedLeftExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				} else {
					$leftTypes = $this->typeSpecifier->create($leftExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				}
				if ($rightExpr instanceof AlwaysRememberedExpr) {
					$rightTypes = $this->typeSpecifier->create($unwrappedRightExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
				} else {
					$rightTypes = $this->typeSpecifier->create($rightExpr, $never, $contextForTypes, $scope)->setRootExpr($expr);
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
			$types = $this->typeSpecifier->create(
				$rightExpr,
				$leftType,
				$context,
				$scope,
			)->setRootExpr($expr);
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$types = $types->unionWith($this->typeSpecifier->create(
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
			$leftTypes = $this->typeSpecifier->create(
				$leftExpr,
				$rightType,
				$context,
				$scope,
			)->setRootExpr($expr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith($this->typeSpecifier->create(
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
			$leftTypes = $this->typeSpecifier->create($leftExpr, $rightType, $context, $scope)->setRootExpr($expr);
			$rightTypes = $this->typeSpecifier->create($rightExpr, $leftType, $context, $scope)->setRootExpr($expr);
			if ($leftExpr instanceof AlwaysRememberedExpr) {
				$leftTypes = $leftTypes->unionWith(
					$this->typeSpecifier->create($unwrappedLeftExpr, $rightType, $context, $scope)->setRootExpr($expr),
				);
			}
			if ($rightExpr instanceof AlwaysRememberedExpr) {
				$rightTypes = $rightTypes->unionWith(
					$this->typeSpecifier->create($unwrappedRightExpr, $leftType, $context, $scope)->setRootExpr($expr),
				);
			}
			return $leftTypes->unionWith($rightTypes);
		} elseif ($context->false()) {
			return $this->typeSpecifier->create($leftExpr, $leftType, $context, $scope)->setRootExpr($expr)->normalize($scope)
				->intersectWith($this->typeSpecifier->create($rightExpr, $rightType, $context, $scope)->setRootExpr($expr)->normalize($scope));
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
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

	private function specifyTypesForConstantBinaryExpression(
		Expr $exprNode,
		Type $constantType,
		TypeSpecifierContext $context,
		Scope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		if (!$context->null() && $constantType->isFalse()->yes()) {
			$types = $this->typeSpecifier->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
			if (!$context->true() && ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			return $types->unionWith($this->typeSpecifier->specifyTypesInCondition(
				$scope,
				$exprNode,
				$context->true() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createFalse()->negate(),
			)->setRootExpr($rootExpr));
		}

		if (!$context->null() && $constantType->isTrue()->yes()) {
			$types = $this->typeSpecifier->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
			if (!$context->true() && ($exprNode instanceof Expr\NullsafeMethodCall || $exprNode instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			return $types->unionWith($this->typeSpecifier->specifyTypesInCondition(
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
				$callType = $this->typeSpecifier->create($exprNode, $constantType, $context, $scope)->setRootExpr($rootExpr);
				$argType = $this->typeSpecifier->create($exprNode->getArgs()[0]->value, $type, $context, $scope)->setRootExpr($rootExpr);
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
				return $this->typeSpecifier->create(
					$exprNode->getArgs()[0]->value,
					$classStringType,
					$context,
					$scope,
				)->setRootExpr($rootExpr);
			}

			if ($argType->isObject()->yes()) {
				return $this->typeSpecifier->create(
					$exprNode->getArgs()[0]->value,
					$objectType,
					$context,
					$scope,
				)->setRootExpr($rootExpr);
			}

			return $this->typeSpecifier->create(
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
				return $this->typeSpecifier->create(
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

}
