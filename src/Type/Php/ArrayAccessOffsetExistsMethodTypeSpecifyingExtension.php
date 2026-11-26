<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use ArrayAccess;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use function count;

#[AutowiredService]
final class ArrayAccessOffsetExistsMethodTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return ArrayAccess::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $methodReflection->getName() === 'offsetExists' && $context->true();
	}

	public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (count($node->getArgs()) < 1) {
			return new SpecifiedTypes();
		}
		$key = $node->getArgs()[0]->value;
		$keyType = $scope->getType($key);

		if (
			!$keyType instanceof ConstantStringType
			&& !$keyType instanceof ConstantIntegerType
		) {
			return new SpecifiedTypes();
		}

		foreach ($scope->getType($node->var)->getObjectClassReflections() as $classReflection) {
			$implementsTags = $classReflection->getImplementsTags();

			if (
				!isset($implementsTags[ArrayAccess::class])
				|| !$implementsTags[ArrayAccess::class]->getType() instanceof GenericObjectType
			) {
				continue;
			}

			$implementsType = $implementsTags[ArrayAccess::class]->getType();
			$arrayAccessGenericTypes = $implementsType->getTypes();
			if (!isset($arrayAccessGenericTypes[1])) {
				continue;
			}

			return $this->typeSpecifier->create(
				$node->var,
				new HasOffsetValueType($keyType, $arrayAccessGenericTypes[1]),
				$context,
				$scope,
			);
		}

		return new SpecifiedTypes();
	}

}
