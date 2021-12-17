<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\TypeCombinator;

class ScopeIsInClassTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private string $isInMethodName;

	private string $removeNullMethodName;

	private ReflectionProvider $reflectionProvider;

	private TypeSpecifier $typeSpecifier;

	public function __construct(
		string $isInMethodName,
		string $removeNullMethodName,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->isInMethodName = $isInMethodName;
		$this->removeNullMethodName = $removeNullMethodName;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return ClassMemberAccessAnswerer::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return $methodReflection->getName() === $this->isInMethodName
			&& !$context->null();
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		$scopeClass = $this->reflectionProvider->getClass(Scope::class);
		$methodVariants = $scopeClass
			->getMethod($this->removeNullMethodName, $scope)
			->getVariants();

		return $this->typeSpecifier->create(
			new MethodCall($node->var, $this->removeNullMethodName),
			TypeCombinator::removeNull(
				ParametersAcceptorSelector::selectSingle($methodVariants)->getReturnType(),
			),
			$context,
			false,
			$scope,
		);
	}

}
