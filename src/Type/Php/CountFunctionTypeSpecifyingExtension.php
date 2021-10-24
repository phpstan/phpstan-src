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
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;

class CountFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return !$context->null()
			&& count($node->getArgs()) >= 1
			&& in_array($functionReflection->getName(), ['sizeof', 'count'], true);
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		if (!(new ArrayType(new MixedType(), new MixedType()))->isSuperTypeOf($scope->getType($node->getArgs()[0]->value))->yes()) {
			return new SpecifiedTypes([], []);
		}

		return $this->typeSpecifier->create($node->getArgs()[0]->value, new NonEmptyArrayType(), $context, false, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
