<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
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

		if ($this->isCovariantWithNonFinalClass($calledOnType, $classReflections)) {
			return null;
		}

		if ($methodReflection->getName() === 'getConstant') {
			return $this->resolveGetConstant($methodCall, $scope, $classReflections);
		}

		$filterType = count($methodCall->getArgs()) >= 1
			? $scope->getType($methodCall->getArgs()[0]->value)
			: null;

		return $this->resolveGetConstants($scope, $classReflections, $filterType);
	}

	/** @param non-empty-string $name */
	private function getConstantType(Scope $scope, ClassReflection $classReflection, string $name): Type
	{
		return $scope->getType(new ClassConstFetch(
			new FullyQualified($classReflection->getName()),
			new Identifier($name),
		));
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
					if ($name === '') {
						continue;
					}
					if ($classReflection->hasConstant($name)) {
						$types[] = $this->getConstantType($scope, $classReflection, $name);
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
			foreach ($this->getConstantNames($classReflection) as $name) {
				$allConstantTypes[] = $this->getConstantType($scope, $classReflection, $name);
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
	private function resolveGetConstants(Scope $scope, array $classReflections, ?Type $filterType): ?Type
	{
		if ($filterType === null) {
			return $this->buildConstantsArray($scope, $classReflections, null, false);
		}

		$filterScalars = $filterType->getConstantScalarValues();
		$intFilters = [];
		foreach ($filterScalars as $scalar) {
			if (!is_int($scalar)) {
				$intFilters = null;
				break;
			}
			$intFilters[] = $scalar;
		}

		if ($intFilters !== null && count($intFilters) === 1) {
			return $this->buildConstantsArray($scope, $classReflections, $intFilters[0], false);
		}

		if ($intFilters !== null && count($intFilters) > 1) {
			$types = [];
			foreach ($intFilters as $filter) {
				$result = $this->buildConstantsArray($scope, $classReflections, $filter, false);
				if ($result !== null) {
					$types[] = $result;
				}
			}

			if (count($types) === 0) {
				return null;
			}

			return TypeCombinator::union(...$types);
		}

		return $this->buildConstantsArray($scope, $classReflections, null, true);
	}

	/**
	 * @param list<ClassReflection> $classReflections
	 */
	private function buildConstantsArray(Scope $scope, array $classReflections, ?int $filter, bool $optional): ?Type
	{
		$types = [];
		foreach ($classReflections as $classReflection) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($this->getConstantNames($classReflection, $filter) as $name) {
				$builder->setOffsetValueType(
					new ConstantStringType($name),
					$this->getConstantType($scope, $classReflection, $name),
					$optional,
				);
			}
			$types[] = $builder->getArray();
		}

		if (count($types) === 0) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * @return list<non-empty-string>
	 */
	private function getConstantNames(ClassReflection $classReflection, ?int $filter = null): array
	{
		$names = [];
		foreach ($classReflection->getNativeReflection()->getReflectionConstants() as $reflectionConstant) {
			if ($filter !== null && ($reflectionConstant->getModifiers() & $filter) === 0) {
				continue;
			}

			$name = $reflectionConstant->getName();
			if ($name === '') {
				continue;
			}

			$names[] = $name;
		}

		return $names;
	}

	/** @param list<ClassReflection> $classReflections */
	private function isCovariantWithNonFinalClass(Type $calledOnType, array $classReflections): bool
	{
		$hasNonFinalClass = false;
		foreach ($classReflections as $classReflection) {
			if (!$classReflection->isFinal() && !$classReflection->isEnum()) {
				$hasNonFinalClass = true;
				break;
			}
		}

		if (!$hasNonFinalClass) {
			return false;
		}

		foreach ($calledOnType->getObjectClassReflections() as $reflectionClassReflection) {
			if ($reflectionClassReflection->getName() !== 'ReflectionClass') {
				continue;
			}
			$variance = $reflectionClassReflection->getCallSiteVarianceMap()->getVariance('T');
			if ($variance !== null && $variance->covariant()) {
				return true;
			}
		}

		return false;
	}

}
