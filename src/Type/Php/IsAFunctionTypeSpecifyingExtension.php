<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
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
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;

class IsAFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'is_a'
			&& isset($node->args[0])
			&& isset($node->args[1])
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$classNameArgExpr = $node->args[1]->value;
		$classNameArgExprType = $scope->getType($classNameArgExpr);
		if (
			$classNameArgExpr instanceof ClassConstFetch
			&& $classNameArgExpr->class instanceof Name
			&& $classNameArgExpr->name instanceof \PhpParser\Node\Identifier
			&& strtolower($classNameArgExpr->name->name) === 'class'
		) {
			$objectType = $scope->resolveTypeByName($classNameArgExpr->class);
			$types = $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		} elseif ($classNameArgExprType instanceof ConstantStringType) {
			$objectType = new ObjectType($classNameArgExprType->getValue());
			$types = $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		} elseif ($classNameArgExprType instanceof GenericClassStringType) {
			$objectType = $classNameArgExprType->getGenericType();
			$types = $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		} elseif ($context->true()) {
			$objectType = new ObjectWithoutClassType();
			$types = $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		} else {
			$types = new SpecifiedTypes();
		}

		if (isset($node->args[2]) && $context->true()) {
			if (!$scope->getType($node->args[2]->value)->isSuperTypeOf(new ConstantBooleanType(true))->no()) {
				$types = $types->intersectWith($this->typeSpecifier->create(
					$node->args[0]->value,
					isset($objectType) ? new GenericClassStringType($objectType) : new ClassStringType(),
					$context
				));
			}
		}

		return $types;
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
