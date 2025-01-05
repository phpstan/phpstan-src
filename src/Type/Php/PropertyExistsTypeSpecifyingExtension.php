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
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectWithoutClassType;
use function count;

final class PropertyExistsTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private PropertyReflectionFinder $propertyReflectionFinder)
	{
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $functionReflection->getName() === 'property_exists'
			&& $context->true()
			&& count($node->getArgs()) >= 2;
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$propertyNames = $scope->getType($node->getArgs()[1]->value)->getConstantStrings();
		if ($propertyNames === []) {
			return new SpecifiedTypes([], []);
		}

		$types = [new ObjectWithoutClassType()];
		foreach ($propertyNames as $propertyNameType) {
			$objectType = $scope->getType($node->getArgs()[0]->value);
			if (!$objectType->isObject()->yes()) {
				return new SpecifiedTypes([], []);
			}

			$propertyNode = new PropertyFetch(
				$node->getArgs()[0]->value,
				new Identifier($propertyNameType->getValue()),
			);

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($propertyNode, $scope);
			if ($propertyReflection !== null) {
				if (!$propertyReflection->isNative()) {
					return new SpecifiedTypes([], []);
				}
			}

			$types[] = new HasPropertyType($propertyNameType->getValue());
		}

		return $this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			new IntersectionType($types),
			$context,
			false,
			$scope,
		);
	}

}
