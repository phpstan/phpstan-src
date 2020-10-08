<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectWithoutClassType;

class PropertyExistsTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private PropertyReflectionFinder $propertyReflectionFinder;

	private TypeSpecifier $typeSpecifier;

	public function __construct(PropertyReflectionFinder $propertyReflectionFinder)
	{
		$this->propertyReflectionFinder = $propertyReflectionFinder;
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $functionReflection->getName() === 'property_exists'
			&& $context->truthy()
			&& count($node->args) >= 2;
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		$propertyNameType = $scope->getType($node->args[1]->value);
		if (!$propertyNameType instanceof ConstantStringType) {
			return new SpecifiedTypes([], []);
		}

		$objectType = $scope->getType($node->args[0]->value);
		if ($objectType instanceof ConstantStringType) {
			return new SpecifiedTypes([], []);
		} elseif ((new ObjectWithoutClassType())->isSuperTypeOf($objectType)->yes()) {
			$propertyNode = new PropertyFetch(
				$node->args[0]->value,
				new Identifier($propertyNameType->getValue())
			);
		} else {
			return new SpecifiedTypes([], []);
		}

		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyNode, $scope);
		foreach ($propertyReflections as $propertyReflection) {
			if (!$propertyReflection->isNative()) {
				return new SpecifiedTypes([], []);
			}
		}

		return $this->typeSpecifier->create(
			$node->args[0]->value,
			new IntersectionType([
				new ObjectWithoutClassType(),
				new HasPropertyType($propertyNameType->getValue()),
			]),
			$context
		);
	}

}
