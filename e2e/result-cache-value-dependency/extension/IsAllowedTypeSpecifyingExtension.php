<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\ObjectType;

/**
 * isAllowed($object) narrows $object to the class in the "allowedClass" parameter.
 */
final class IsAllowedTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $functionReflection->getName() === 'ResultCacheE2EValueDependency\isAllowed' && $context->true() && isset($node->getArgs()[0]);
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$scope->trackValueDependency(ParameterValueExtension::class, 'allowedClass');
		$class = Container::getParameter('allowedClass');
		if ($class === null) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create($node->getArgs()[0]->value, new ObjectType($class), $context, $scope);
	}

}
