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
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;

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

		$types = $this->specifyTypesHelper($node, $scope, $context);

		if (
			isset($node->args[2])
			&& $context->true()
			&& !$scope->getType($node->args[2]->value)->isSuperTypeOf(new ConstantBooleanType(true))->no()
		) {
			$types = $types->intersectWith($this->typeSpecifier->create($node->args[0]->value, new StringType(), $context));
		}

		return $types;
	}

	private function specifyTypesHelper(FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$classNameArgExpr = $node->args[1]->value;

		if ($classNameArgExpr instanceof ClassConstFetch
			&& $classNameArgExpr->class instanceof Name
			&& $classNameArgExpr->name instanceof \PhpParser\Node\Identifier
			&& strtolower($classNameArgExpr->name->name) === 'class'
		) {
			$className = $scope->resolveName($classNameArgExpr->class);
			if (strtolower($classNameArgExpr->class->toString()) === 'static') {
				$objectType = new StaticType($className);
			} else {
				$objectType = new ObjectType($className);
			}

			return $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		}

		$classNameArgExprType = $scope->getType($classNameArgExpr);
		if ($classNameArgExprType instanceof ConstantStringType) {
			$objectType = new ObjectType($classNameArgExprType->getValue());
			return $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		}

		if ($context->true()) {
			$objectType = new ObjectWithoutClassType();
			return $this->typeSpecifier->create($node->args[0]->value, $objectType, $context);
		}

		return new SpecifiedTypes();
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
