<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function count;
use function strtolower;

class InArrayFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
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
		if (count($node->getArgs()) < 3) {
			return new SpecifiedTypes();
		}
		$strictNodeType = $scope->getType($node->getArgs()[2]->value);
		if (!(new ConstantBooleanType(true))->isSuperTypeOf($strictNodeType)->yes()) {
			return new SpecifiedTypes();
		}

		$needleType = $scope->getType($node->getArgs()[0]->value);
		$arrayType = $scope->getType($node->getArgs()[1]->value);
		$arrayValueType = $arrayType->getIterableValueType();

		$specifiedTypes = new SpecifiedTypes();

		if (
			$arrayType instanceof ConstantArrayType && !$arrayType->isEmpty()
			&& count(TypeUtils::getConstantScalars($needleType)) === 0 && $arrayValueType->isSuperTypeOf($needleType)->yes()
		) {
			// Avoid false-positives with e.g. a string needle and array{'self', string} as haystack
			// For such cases there seems to be nothing more that we can specify unfortunately
			return $specifiedTypes;
		}

		if (
			$context->truthy()
			|| count(TypeUtils::getConstantScalars($arrayValueType)) > 0
			|| count(TypeUtils::getEnumCaseObjects($arrayValueType)) > 0
		) {
			// Specify needle type
			$specifiedTypes = $this->typeSpecifier->create(
				$node->getArgs()[0]->value,
				$arrayValueType,
				$context,
				false,
				$scope,
			);
		}

		// If e.g. needle is 'a' and haystack non-empty-array<int, 'a'> we can be sure that this always evaluates to true
		// Belows HasOffset::isSuperTypeOf cannot deal with that since it calls ArrayType::hasOffsetValueType and that returns maybe at max
		if ($needleType instanceof ConstantScalarType && $arrayType->isIterableAtLeastOnce()->yes() && $arrayValueType->equals($needleType)) {
			return $specifiedTypes;
		}

		if (
			$context->truthy()
			|| count(TypeUtils::getConstantScalars($needleType)) > 0
			|| count(TypeUtils::getEnumCaseObjects($needleType)) > 0
		) {
			// Specify haystack type
			if ($arrayType instanceof ConstantArrayType) {
				$newArrayType = TypeTraverser::map(
					$arrayType->getOffsetType($needleType),
					static function (Type $offsetType, callable $traverse) use ($context, $arrayType, $needleType): Type {
						if ($offsetType instanceof UnionType || $offsetType instanceof IntersectionType) {
							 return $traverse($offsetType);
						}

						$resultArray = $arrayType;
						if ($context->truthy()) {
							$resultArray = $resultArray->makeOffsetRequired($offsetType);
						} elseif ($offsetType instanceof ConstantIntegerType && $resultArray->isOptionalKey($offsetType->getValue())) {
							$resultArray = $resultArray->unsetOffset($offsetType);
						}

						if ($offsetType instanceof ConstantIntegerType && $resultArray instanceof ConstantArrayType && $resultArray->hasOffsetValueType($offsetType)->yes()) {
							// If haystack is e.g. {string, string|null} and needle null, we can further narrow string|null
							$builder = ConstantArrayTypeBuilder::createFromConstantArray($resultArray);
							$builder->setOffsetValueType(
								$offsetType,
								$context->truthy() ? $needleType : TypeCombinator::remove($resultArray->getOffsetValueType($offsetType), $needleType),
								$resultArray->isOptionalKey($offsetType->getValue()),
							);
							$resultArray = $builder->getArray();
						}

						return $resultArray;
					},
				);
			} else {
				if ($context->truthy()) {
					$newArrayType = TypeCombinator::intersect(
						new ArrayType(new MixedType(), TypeCombinator::union($arrayValueType, $needleType)),
						new HasOffsetType(new MixedType(), $needleType),
						new NonEmptyArrayType(),
					);
				} else {
					$newArrayType = new ArrayType(
						new MixedType(),
						TypeCombinator::remove($arrayValueType, $needleType),
					);
				}
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
				$node->getArgs()[1]->value,
				$newArrayType,
				TypeSpecifierContext::createTrue(),
				false,
				$scope,
			));
		}

		return $specifiedTypes;
	}

}
