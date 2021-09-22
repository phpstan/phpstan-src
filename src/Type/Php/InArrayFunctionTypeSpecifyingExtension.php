<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

class InArrayFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

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
			return new SpecifiedTypes([], []);
		}

		$needleType = $scope->getType($node->getArgs()[0]->value);
		$arrayType = $scope->getType($node->getArgs()[1]->value);
		$arrayValueType = $arrayType->getIterableValueType();


		if ($arrayValueType instanceof UnionType|| $arrayValueType instanceof ConstantScalarType) {
			if ($arrayType->isIterableAtLeastOnce()->yes()) {
				if ($arrayValueType instanceof ConstantScalarType && $needleType instanceof ConstantScalarType) {
					if ($arrayValueType->getValue() !== $needleType->getValue()) {
						return new SpecifiedTypes();
					}
				} else {
					return new SpecifiedTypes();
				}
			} elseif ($needleType instanceof ConstantScalarType) {
				return new SpecifiedTypes();
			}
		}

		if (
			$context->truthy()
			|| count(TypeUtils::getConstantScalars($arrayValueType)) > 0
		) {
			return $this->typeSpecifier->create(
				$node->getArgs()[0]->value,
				$arrayValueType,
				$context,
				false,
				$scope
			);
		}

		return new SpecifiedTypes([], []);
	}

}
