<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use TypeSpecifierContextReturnTypeTest\ContextReturnType;
use function str_starts_with;

class TypeSpecifierContextReturnTypeExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return ContextReturnType::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return str_starts_with($methodReflection->getName(), 'returns');
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		return $this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			$context->getReturnType(),
			$context,
			$scope,
		);
	}

}
