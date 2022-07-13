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
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
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

		// "Simple" all constant cases that always evaluate to true still need a nudge (e.g. needle 'foo' in haystack {'foo', 'bar'})
		if ($arrayType instanceof ConstantArrayType && $needleType instanceof ConstantScalarType) {
			foreach ($arrayType->getValueTypes() as $i => $valueType) {
				if (!$arrayType->isOptionalKey($i) && $valueType->equals($needleType)) {
					return $specifiedTypes;
				}
			}
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
			if ($context->truthy()) {
				$newArrayType = TypeCombinator::intersect(
					new ArrayType(new MixedType(), TypeCombinator::union($arrayValueType, $needleType)),
					new HasOffsetType(new MixedType(), $needleType),
					new NonEmptyArrayType(),
				);
			} else {
				$newArrayType = new ArrayType(new MixedType(), TypeCombinator::remove($arrayValueType, $needleType));
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
