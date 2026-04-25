<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionClass;
use function count;
use function is_int;

#[AutowiredService]
final class ReflectionClassGetConstantsDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return ReflectionClass::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getConstant'
			|| $methodReflection->getName() === 'getConstants';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$calledOnType = $scope->getType($methodCall->var);
		$reflectionType = $calledOnType->getTemplateType(ReflectionClass::class, 'T');

		if ((new ObjectWithoutClassType())->isSuperTypeOf($reflectionType)->no()) {
			return null;
		}

		$classReflections = $reflectionType->getObjectClassReflections();
		if (count($classReflections) === 0) {
			return null;
		}

		if ($methodReflection->getName() === 'getConstant') {
			return $this->resolveGetConstant($methodCall, $scope, $classReflections);
		}

		$filterType = count($methodCall->getArgs()) >= 1
			? $scope->getType($methodCall->getArgs()[0]->value)
			: null;

		return $this->resolveGetConstants($classReflections, $filterType);
	}

	/**
	 * @param list<ClassReflection> $classReflections
	 */
	private function resolveGetConstant(MethodCall $methodCall, Scope $scope, array $classReflections): ?Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return null;
		}

		$nameType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantNames = $nameType->getConstantStrings();

		if (count($constantNames) > 0) {
			$types = [];
			foreach ($classReflections as $classReflection) {
				foreach ($constantNames as $constantName) {
					$name = $constantName->getValue();
					if ($classReflection->isEnum() && $classReflection->hasEnumCase($name)) {
						$types[] = new EnumCaseObjectType($classReflection->getName(), $name);
					} elseif ($classReflection->hasConstant($name)) {
						$types[] = $classReflection->getConstant($name)->getValueType();
					} else {
						$types[] = new ConstantBooleanType(false);
					}
				}
			}

			if (count($types) === 0) {
				return null;
			}

			return TypeCombinator::union(...$types);
		}

		$allConstantTypes = [];
		foreach ($classReflections as $classReflection) {
			foreach ($this->getClassConstants($classReflection) as [$name, $valueType]) {
				$allConstantTypes[] = $valueType;
			}
		}

		if (count($allConstantTypes) === 0) {
			return new ConstantBooleanType(false);
		}

		$allConstantTypes[] = new ConstantBooleanType(false);

		return TypeCombinator::union(...$allConstantTypes);
	}

	/**
	 * @param list<ClassReflection> $classReflections
	 */
	private function resolveGetConstants(array $classReflections, ?Type $filterType): ?Type
	{
		$filter = null;
		$filterIsUncertain = false;
		if ($filterType !== null) {
			$filterScalars = $filterType->getConstantScalarValues();
			if (count($filterScalars) === 1 && is_int($filterScalars[0])) {
				$filter = $filterScalars[0];
			} else {
				$filterIsUncertain = true;
			}
		}

		$types = [];
		foreach ($classReflections as $classReflection) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($this->getClassConstants($classReflection, $filter) as [$name, $valueType]) {
				$builder->setOffsetValueType(new ConstantStringType($name), $valueType, $filterIsUncertain);
			}
			$types[] = $builder->getArray();
		}

		if (count($types) === 0) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * @return list<array{string, Type}>
	 */
	private function getClassConstants(ClassReflection $classReflection, ?int $filter = null): array
	{
		$constants = [];
		foreach ($classReflection->getNativeReflection()->getReflectionConstants() as $reflectionConstant) {
			$constantName = $reflectionConstant->getName();

			if ($filter !== null && ($reflectionConstant->getModifiers() & $filter) === 0) {
				continue;
			}

			if ($classReflection->isEnum() && $classReflection->hasEnumCase($constantName)) {
				$constants[] = [$constantName, new EnumCaseObjectType($classReflection->getName(), $constantName)];
				continue;
			}

			if (!$classReflection->hasConstant($constantName)) {
				continue;
			}

			$constants[] = [$constantName, $classReflection->getConstant($constantName)->getValueType()];
		}

		return $constants;
	}

}
