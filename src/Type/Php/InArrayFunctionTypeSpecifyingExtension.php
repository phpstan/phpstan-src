<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

#[AutowiredService]
final class InArrayFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'in_array'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		$argsCount = count($args);
		if ($argsCount < 2) {
			return new SpecifiedTypes();
		}

		$isStrictComparison = false;
		if ($argsCount >= 3) {
			$strictNodeType = $scope->getType($args[2]->value);
			$isStrictComparison = $strictNodeType->isTrue()->yes();
		}

		$needleExpr = $args[0]->value;
		$arrayExpr = $args[1]->value;

		$needleType = $scope->getType($needleExpr);
		$arrayType = $scope->getType($arrayExpr);
		$arrayValueType = $arrayType->getIterableValueType();

		$isStrictComparison = $isStrictComparison
			|| $needleType->isEnum()->yes()
			|| $arrayValueType->isEnum()->yes()
			|| ($needleType->isString()->yes() && $arrayValueType->isString()->yes())
			|| ($needleType->isInteger()->yes() && $arrayValueType->isInteger()->yes())
			|| ($needleType->isFloat()->yes() && $arrayValueType->isFloat()->yes())
			|| ($needleType->isBoolean()->yes() && $arrayValueType->isBoolean()->yes());

		if ($arrayExpr instanceof Array_) {
			$types = null;
			foreach ($arrayExpr->items as $item) {
				if ($item->unpack) {
					$types = null;
					break;
				}

				if ($isStrictComparison) {
					$itemTypes = $this->typeSpecifier->specifyTypesInCondition($scope, new Identical($needleExpr, $item->value), $context);
				} else {
					$itemTypes = $this->typeSpecifier->specifyTypesInCondition($scope, new Equal($needleExpr, $item->value), $context);
				}

				if ($types === null) {
					$types = $itemTypes;
					continue;
				}

				$types = $context->true() ? $types->normalize($scope)->intersectWith($itemTypes->normalize($scope)) : $types->unionWith($itemTypes);
			}

			if ($types !== null) {
				return $types;
			}
		}

		if (!$isStrictComparison) {
			if (
				$context->true()
				&& $arrayType->isArray()->yes()
				&& $arrayType->getIterableValueType()->isSuperTypeOf($needleType)->yes()
			) {
				return $this->typeSpecifier->create(
					$args[1]->value,
					TypeCombinator::intersect($arrayType, new NonEmptyArrayType()),
					$context,
					$scope,
				);
			}

			return new SpecifiedTypes();
		}

		$specifiedTypes = new SpecifiedTypes();
		$narrowingValueType = $this->computeNeedleNarrowingType($context, $needleType, $arrayType, $arrayValueType);
		if ($narrowingValueType !== null) {
			$specifiedTypes = $this->typeSpecifier->create(
				$needleExpr,
				$narrowingValueType,
				$context,
				$scope,
			);
			if ($needleExpr instanceof AlwaysRememberedExpr) {
				$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
					$needleExpr->getExpr(),
					$narrowingValueType,
					$context,
					$scope,
				));
			}
		}

		if (
			$context->true()
			|| (
				$context->false()
				&& count($needleType->getFiniteTypes()) === 1
			)
		) {
			if ($context->true()) {
				$arrayValueType = TypeCombinator::union($arrayValueType, $needleType);
			} else {
				$arrayValueType = TypeCombinator::remove($arrayValueType, $needleType);
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
				$args[1]->value,
				new ArrayType(new MixedType(), $arrayValueType),
				TypeSpecifierContext::createTrue(),
				$scope,
			));
		}

		if ($context->true() && $arrayType->isArray()->yes()) {
			$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
				$args[1]->value,
				TypeCombinator::intersect($arrayType, new NonEmptyArrayType()),
				$context,
				$scope,
			));
		}

		return $specifiedTypes;
	}

	/**
	 * Computes the type to narrow the needle against, or null if no narrowing should occur.
	 * In true context, returns the array value type directly.
	 * In false context, returns only the values guaranteed to be in every possible variant of the array.
	 */
	private function computeNeedleNarrowingType(TypeSpecifierContext $context, Type $needleType, Type $arrayType, Type $arrayValueType): ?Type
	{
		if ($context->true()) {
			return $arrayValueType;
		}

		if (
			!$context->false()
			|| count($needleType->getFiniteTypes()) === 0
			|| !$arrayType->isIterableAtLeastOnce()->yes()
		) {
			return null;
		}

		$arrays = $arrayType->getArrays();
		$guaranteedValueTypePerArray = [];
		foreach ($arrays as $array) {
			if ($array instanceof ConstantArrayType) {
				$innerGuaranteeValueType = [];
				foreach ($array->getValueTypes() as $i => $valueType) {
					if ($array->isOptionalKey($i)) {
						continue;
					}

					$finiteTypes = $valueType->getFiniteTypes();
					if (count($finiteTypes) !== 1) {
						continue;
					}

					$innerGuaranteeValueType[] = $finiteTypes[0];
				}

				if (count($innerGuaranteeValueType) === 0) {
					return null;
				}

				$guaranteedValueTypePerArray[] = TypeCombinator::union(...$innerGuaranteeValueType);
			} else {
				$finiteValueType = $array->getIterableValueType()->getFiniteTypes();
				if (count($finiteValueType) !== 1) {
					return null;
				}

				$guaranteedValueTypePerArray[] = $finiteValueType[0];
			}
		}

		if (count($guaranteedValueTypePerArray) === 0) {
			return null;
		}

		$guaranteedValueType = $guaranteedValueTypePerArray[0];
		for ($i = 1, $count = count($guaranteedValueTypePerArray); $i < $count; $i++) {
			$guaranteedValueType = TypeCombinator::intersect($guaranteedValueType, $guaranteedValueTypePerArray[$i]);
		}
		if (count($guaranteedValueType->getFiniteTypes()) === 0) {
			return null;
		}

		return $guaranteedValueType;
	}

}
