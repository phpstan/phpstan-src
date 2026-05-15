<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Iterator;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\MethodTypeSpecifyingExtension;

#[AutowiredService]
final class IteratorValidMethodTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return Iterator::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
	{
		return $methodReflection->getName() === 'valid'
			&& $context->truthy();
	}

	public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$calledOnType = $scope->getType($node->var);
		$types = new SpecifiedTypes();

		foreach (['current', 'key'] as $methodName) {
			$methodCallExpr = new MethodCall($node->var, new Identifier($methodName));

			$targetMethodReflection = $scope->getMethodReflection($calledOnType, $methodName);
			if ($targetMethodReflection === null) {
				continue;
			}

			$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
				$scope,
				[],
				$targetMethodReflection->getVariants(),
			);

			$baseReturnType = $parametersAcceptor->getReturnType();

			$types = $types->unionWith($this->typeSpecifier->create(
				$methodCallExpr,
				$baseReturnType,
				TypeSpecifierContext::createTrue(),
				$scope,
			));
		}

		return $types;
	}

}
