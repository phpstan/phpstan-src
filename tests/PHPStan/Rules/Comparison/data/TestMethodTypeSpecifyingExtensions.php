<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MethodTypeSpecifyingExtension;

class AssertNotInt implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return \PHPStan\Tests\AssertionClass::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $methodReflection->getName() === 'assertNotInt'
			&& count($node->args) > 0;
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		return $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			new \PhpParser\Node\Expr\BooleanNot(
				new \PhpParser\Node\Expr\FuncCall(
					new \PhpParser\Node\Name('is_int'),
					[
						$node->args[0],
					]
				)
			),
			TypeSpecifierContext::createTruthy()
		);
	}

}

class FooIsSame implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return \ImpossibleMethodCall\Foo::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $methodReflection->getName() === 'isSame'
			&& count($node->args) >= 2;
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		return $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			new \PhpParser\Node\Expr\BinaryOp\Identical(
				$node->args[0]->value,
				$node->args[1]->value
			),
			$context
		);
	}

}

class FooIsNotSame implements MethodTypeSpecifyingExtension,
	TypeSpecifierAwareExtension {

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return \ImpossibleMethodCall\Foo::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $methodReflection->getName() === 'isNotSame'
			&& count($node->args) >= 2;
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		return $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			new \PhpParser\Node\Expr\BinaryOp\NotIdentical(
				$node->args[0]->value,
				$node->args[1]->value
			),
			$context
		);
	}

}

class FooIsEqual implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return \ImpossibleMethodCall\Foo::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $methodReflection->getName() === 'isSame'
			&& count($node->args) >= 2;
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		return $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			new \PhpParser\Node\Expr\BinaryOp\Equal(
				$node->args[0]->value,
				$node->args[1]->value
			),
			$context
		);
	}

}

class FooIsNotEqual implements MethodTypeSpecifyingExtension,
	TypeSpecifierAwareExtension {

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return \ImpossibleMethodCall\Foo::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $methodReflection->getName() === 'isNotSame'
			&& count($node->args) >= 2;
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		return $this->typeSpecifier->specifyTypesInCondition(
			$scope,
			new \PhpParser\Node\Expr\BinaryOp\NotEqual(
				$node->args[0]->value,
				$node->args[1]->value
			),
			$context
		);
	}

}
