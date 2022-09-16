<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function strtolower;

class ArrayIsListFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return strtolower($functionReflection->getName()) === 'array_is_list'
			&& !$context->null();
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$arrayArg = $node->getArgs()[0]->value ?? null;
		if ($arrayArg === null) {
			return new SpecifiedTypes();
		}

		$valueType = $scope->getType($arrayArg);
		if ($valueType instanceof ConstantArrayType) {
			return $this->typeSpecifier->create($arrayArg, $valueType->getValuesArray(), $context, false, $scope);
		}

		return $this->typeSpecifier->create(
			$arrayArg,
			TypeCombinator::intersect(
				new ArrayType(new IntegerType(), $valueType->getIterableValueType()),
				new AccessoryArrayListType(),
				...TypeUtils::getAccessoryTypes($valueType),
			),
			$context,
			false,
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
