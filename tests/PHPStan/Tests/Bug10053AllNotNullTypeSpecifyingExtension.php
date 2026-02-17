<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use Bug10053\MyAssert;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\TypeCombinator;
use function count;

class Bug10053AllNotNullTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return MyAssert::class;
	}

	public function isStaticMethodSupported(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $staticMethodReflection->getName() === 'allNotNull';
	}

	public function specifyTypes(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$argExpr = $node->getArgs()[0]->value;
		$argType = $scope->getType($argExpr);

		$arrayTypes = $argType->getArrays();
		if (count($arrayTypes) <= 0) {
			return new SpecifiedTypes([], []);
		}

		$newArrayTypes = [];
		foreach ($arrayTypes as $arrayType) {
			$itemType = TypeCombinator::removeNull($arrayType->getItemType());
			$newArrayTypes[] = new ArrayType($arrayType->getKeyType(), $itemType);
		}
		$specifiedType = TypeCombinator::union(...$newArrayTypes);

		return $this->typeSpecifier->create(
			$argExpr,
			$specifiedType,
			TypeSpecifierContext::createTrue(),
			$scope,
		);
	}

}
