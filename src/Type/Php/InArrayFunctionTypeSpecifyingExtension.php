<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
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
			return new SpecifiedTypes([], []);
		}

		$needleType = $scope->getType($node->getArgs()[0]->value);
		$arrayValueType = $scope->getType($node->getArgs()[1]->value)->getIterableValueType();

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
		} else {
			$specifiedTypes = new SpecifiedTypes([], []);
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

		return $specifiedTypes;
	}

}
