<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function count;
use function strtolower;

class IsSubclassOfFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'is_subclass_of'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (count($node->getArgs()) < 2) {
			return new SpecifiedTypes();
		}
		$classType = $scope->getType($node->getArgs()[1]->value);
		$allowStringType = isset($node->getArgs()[2]) ? $scope->getType($node->getArgs()[2]->value) : new ConstantBooleanType(true);
		$allowString = !$allowStringType->equals(new ConstantBooleanType(false));

		if (!$classType instanceof ConstantStringType && !$context->truthy()) {
			return new SpecifiedTypes([], []);
		}

		$type = TypeTraverser::map($classType, static function (Type $type, callable $traverse) use ($allowString): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}
			if ($type instanceof ConstantStringType) {
				if ($allowString) {
					return TypeCombinator::union(
						new ObjectType($type->getValue()),
						new GenericClassStringType(new ObjectType($type->getValue())),
					);
				}
				return new ObjectType($type->getValue());
			}
			if ($type instanceof GenericClassStringType) {
				if ($allowString) {
					return TypeCombinator::union(
						$type->getGenericType(),
						$type,
					);
				}
				return $type->getGenericType();
			}
			if ($allowString) {
				return TypeCombinator::union(
					new ObjectWithoutClassType(),
					new ClassStringType(),
				);
			}
			return new ObjectWithoutClassType();
		});

		return $this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			$type,
			$context,
			false,
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
