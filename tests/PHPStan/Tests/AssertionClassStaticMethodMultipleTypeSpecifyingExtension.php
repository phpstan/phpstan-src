<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;

class AssertionClassStaticMethodMultipleTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension
{

	public function getClass(): string
	{
		return AssertionClass::class;
	}

	public function isStaticMethodSupported(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $staticMethodReflection->getName() === 'assertInt';
	}

	public function specifyTypes(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		return new SpecifiedTypes(['$foo' => [$node->getArgs()[0]->value, IntegerRangeType::createAllGreaterThan(0)]]);
	}

}
