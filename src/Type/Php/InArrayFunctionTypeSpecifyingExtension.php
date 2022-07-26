<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
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
			$specifiedTypes = $this->typeSpecifier->create(
				$node->getArgs()[0]->value,
				$arrayValueType,
				$context,
				false,
				$scope,
			);
		}

		if (
			$context->truthy()
			|| count(TypeUtils::getConstantScalars($needleType)) > 0
			|| count(TypeUtils::getEnumCaseObjects($needleType)) > 0
		) {
			if ($context->truthy()) {
				$arrayValueType = TypeCombinator::union($arrayValueType, $needleType);
			} else {
				$arrayValueType = TypeCombinator::remove($arrayValueType, $needleType);
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
				$node->getArgs()[1]->value,
				new ArrayType(new MixedType(), $arrayValueType),
				TypeSpecifierContext::createTrue(),
				false,
				$scope,
			));
		}

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

					if (
						$offsetType instanceof ConstantIntegerType
						&& $resultArray instanceof ConstantArrayType
						&& $resultArray->hasOffsetValueType($offsetType)->yes()
					) {
						// If haystack is e.g. {string, string|null} and needle null, we can further narrow string|null
						$builder = ConstantArrayTypeBuilder::createFromConstantArray($resultArray);
						$builder->setOffsetValueType(
							$offsetType,
							$context->truthy()
								? $needleType
								: TypeCombinator::remove($resultArray->getOffsetValueType($offsetType), $needleType),
							$resultArray->isOptionalKey($offsetType->getValue()),
						);
						$resultArray = $builder->getArray();
					}

					return $resultArray;
				},
			);

			$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
				$node->getArgs()[1]->value,
				$newArrayType,
				TypeSpecifierContext::createTrue(),
				false,
				$scope,
			));
		}

		if ($context->truthy() && $arrayType->isArray()->yes()) {
			$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
				$node->getArgs()[1]->value,
				TypeCombinator::intersect($arrayType, new NonEmptyArrayType()),
				$context,
				false,
				$scope,
			));
		}

		return $specifiedTypes;
	}

}
