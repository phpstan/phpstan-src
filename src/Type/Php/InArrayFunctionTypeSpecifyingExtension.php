<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
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
				$specifiedTypes = $this->typeSpecifier->create(
					$args[1]->value,
					TypeCombinator::intersect($arrayType, new NonEmptyArrayType()),
					$context,
					$scope,
				);

				return $specifiedTypes->setRootExpr(new ConstFetch(new Name('__PHPSTAN_FAUX_CONSTANT')));
			}

			return new SpecifiedTypes();
		}

		$specifiedTypes = new SpecifiedTypes();
		$originalArrayValueType = $arrayValueType;
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

		return $specifiedTypes->setRootExpr($this->determineRootExpr($needleType, $arrayType, $originalArrayValueType));
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

		$constantArrays = $arrayType->getConstantArrays();
		$guaranteedValueTypePerArray = [];
		if (count($constantArrays) > 0) {
			foreach ($constantArrays as $constantArray) {
				$innerGuaranteeValueType = [];
				foreach ($constantArray->getValueTypes() as $i => $valueType) {
					if ($constantArray->isOptionalKey($i)) {
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
			}
		} else {
			$arrays = $arrayType->getArrays();
			foreach ($arrays as $array) {
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

		$guaranteedValueType = TypeCombinator::intersect(...$guaranteedValueTypePerArray);
		if (count($guaranteedValueType->getFiniteTypes()) === 0) {
			return null;
		}

		return $guaranteedValueType;
	}

	/**
	 * Returns a rootExpr that ImpossibleCheckTypeHelper evaluates instead of
	 * using sureTypes-based detection. This bypasses scope leakage issues where
	 * expressions (e.g. enum ClassConstFetch) are marked as "specified" by
	 * prior in_array calls in the same scope.
	 *
	 * Returns ConstFetch('false') for incompatible types (always false),
	 * ConstFetch('true') when the needle is guaranteed to be found (always true),
	 * or ConstFetch('__PHPSTAN_FAUX_CONSTANT') for indeterminate cases.
	 */
	private function determineRootExpr(Type $needleType, Type $arrayType, Type $arrayValueType): ConstFetch
	{
		// Types are incompatible -> always false
		if ($arrayValueType->isSuperTypeOf($needleType)->no()) {
			return new ConstFetch(new Name('false'));
		}

		// Check if in_array is guaranteed to always return true
		if ($this->isGuaranteedToContainNeedle($needleType, $arrayType)) {
			return new ConstFetch(new Name('true'));
		}

		// Indeterminate: types are compatible but can't guarantee result.
		// Set rootExpr to prevent false impossibility detection.
		return new ConstFetch(new Name('__PHPSTAN_FAUX_CONSTANT'));
	}

	private function isGuaranteedToContainNeedle(Type $needleType, Type $arrayType): bool
	{
		if (!$arrayType->isIterableAtLeastOnce()->yes()) {
			return false;
		}

		if (count($needleType->getFiniteTypes()) !== 1) {
			return false;
		}

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			foreach ($constantArrays as $constantArray) {
				$hasGuaranteedMatch = false;
				foreach ($constantArray->getValueTypes() as $i => $valueType) {
					if ($constantArray->isOptionalKey($i)) {
						continue;
					}

					$constantScalarTypes = $valueType->getConstantScalarTypes();
					if (count($constantScalarTypes) > 1) {
						continue;
					}

					foreach ($constantScalarTypes as $constantScalarType) {
						if ($constantScalarType->isSuperTypeOf($needleType)->yes()) {
							$hasGuaranteedMatch = true;
							break 2;
						}
					}
				}
				if (!$hasGuaranteedMatch) {
					return false;
				}
			}
		} else {
			$arrays = $arrayType->getArrays();
			if (count($arrays) === 0) {
				return false;
			}

			foreach ($arrays as $array) {
				$finiteTypes = $array->getIterableValueType()->getFiniteTypes();
				if (count($finiteTypes) !== 1) {
					return false;
				}
				if (!$needleType->isSuperTypeOf($finiteTypes[0])->yes()) {
					return false;
				}
			}
		}

		return true;
	}

}
