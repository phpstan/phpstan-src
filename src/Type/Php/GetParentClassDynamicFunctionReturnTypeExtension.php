<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_map;
use function count;

class GetParentClassDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection
	): bool
	{
		return $functionReflection->getName() === 'get_parent_class';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle(
			$functionReflection->getVariants()
		)->getReturnType();
		if (count($functionCall->getArgs()) === 0) {
			if ($scope->isInTrait()) {
				return $defaultReturnType;
			}
			if ($scope->isInClass()) {
				return $this->findParentClassType(
					$scope->getClassReflection()
				);
			}

			return new ConstantBooleanType(false);
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($scope->isInTrait() && TypeUtils::findThisType($argType) !== null) {
			return $defaultReturnType;
		}

		$constantStrings = TypeUtils::getConstantStrings($argType);
		if (count($constantStrings) > 0) {
			return TypeCombinator::union(...array_map(function (ConstantStringType $stringType): Type {
				return $this->findParentClassNameType($stringType->getValue());
			}, $constantStrings));
		}

		$classNames = TypeUtils::getDirectClassNames($argType);
		if (count($classNames) > 0) {
			return TypeCombinator::union(...array_map(function (string $classNames): Type {
				return $this->findParentClassNameType($classNames);
			}, $classNames));
		}

		return $defaultReturnType;
	}

	private function findParentClassNameType(string $className): Type
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return new UnionType([
				new ClassStringType(),
				new ConstantBooleanType(false),
			]);
		}

		return $this->findParentClassType($this->reflectionProvider->getClass($className));
	}

	private function findParentClassType(
		ClassReflection $classReflection
	): Type
	{
		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return new ConstantBooleanType(false);
		}

		return new ConstantStringType($parentClass->getName(), true);
	}

}
