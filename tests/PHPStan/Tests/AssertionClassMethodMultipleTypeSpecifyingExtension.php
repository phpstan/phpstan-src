<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\MethodTypeSpecifyingExtension;

class AssertionClassMethodMultipleTypeSpecifyingExtension implements MethodTypeSpecifyingExtension
{

	public function getClass(): string
	{
		return AssertionClass::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $methodReflection->getName() === 'assertString';
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		return new SpecifiedTypes(['$foo' => [$node->getArgs()[0]->value, new AccessoryNonEmptyStringType()]]);
	}

}
