<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectWithoutClassType;
use function count;

#[AutowiredService]
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
		$propertyNameType = $scope->getType($node->getArgs()[1]->value);
		if (!$propertyNameType instanceof ConstantStringType) {
			return $this->typeSpecifier->create(
				new FuncCall(new FullyQualified('property_exists'), $node->getRawArgs()),
				new ConstantBooleanType(true),
				$context,
				$scope,
			);
		}

		if ($propertyNameType->getValue() === '') {
			return new SpecifiedTypes([], []);
		}

		$objectType = $scope->getType($node->getArgs()[0]->value);
		if ($objectType instanceof ConstantStringType) {
			return new SpecifiedTypes([], []);
		} elseif ($objectType->isObject()->yes()) {
			$propertyNode = new PropertyFetch(
				$node->getArgs()[0]->value,
				new Identifier($propertyNameType->getValue()),
			);
		} else {
			return new SpecifiedTypes([], []);
		}

		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($propertyNode, $scope);
		if ($propertyReflection !== null) {
			if (!$propertyReflection->isNative()) {
				return new SpecifiedTypes([], []);
			}
		}

		return $this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			new IntersectionType([
				new ObjectWithoutClassType(),
				new HasPropertyType($propertyNameType->getValue()),
			]),
			$context,
			$scope,
		);
	}

}
