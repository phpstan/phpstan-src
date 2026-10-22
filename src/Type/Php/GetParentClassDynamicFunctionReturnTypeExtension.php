<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_map;
use function count;

#[AutowiredService]
final class GetParentClassDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return $functionReflection->getName() === 'get_parent_class';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) === 0) {
			if ($scope->isInTrait()) {
				return null;
			}
			if ($scope->isInClass()) {
				return $this->findParentClassType(
					$scope->getClassReflection(),
				);
			}

			return new ConstantBooleanType(false);
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($scope->isInTrait() && TypeUtils::findThisType($argType) !== null) {
			return null;
		}

		$constantStrings = $argType->getConstantStrings();
		if (count($constantStrings) > 0) {
			return TypeCombinator::union(...array_map(fn (ConstantStringType $stringType): Type => $this->findParentClassNameType($stringType->getValue()), $constantStrings));
		}

		// A `static::class` string refers to the same late-static-bound type as `$this`/`static`,
		// so unwrap it and reuse the object handling below. Non-class-string types resolve to
		// an ErrorType here, so they are left alone.
		$valueType = $argType;
		$classStringObjectType = $argType->getClassStringObjectType();
		if ($classStringObjectType instanceof StaticType) {
			$valueType = $classStringObjectType;
		}

		// Inside a trait a late-static-bound value cannot be resolved to a concrete parent,
		// same as `$this` above.
		if ($scope->isInTrait() && $valueType instanceof StaticType) {
			return null;
		}

		$classNames = $valueType->getObjectClassNames();
		if (count($classNames) > 0) {
			// A `$this`/`static` value can be an instance of a subclass through late static
			// binding. For a non-final class the parent class is then not pinned to the declared
			// parent: a direct child's parent is the class itself, a deeper descendant's parent is
			// some subclass. So the result also includes `class-string<Class>`.
			$isLateStaticBound = $valueType instanceof StaticType;

			$types = [];
			foreach ($classNames as $className) {
				$types[] = $this->findParentClassNameType($className);

				if (
					!$isLateStaticBound
					|| !$this->reflectionProvider->hasClass($className)
					|| $this->reflectionProvider->getClass($className)->isFinal()
				) {
					continue;
				}

				$types[] = new GenericClassStringType(new ObjectType($className));
			}

			return TypeCombinator::union(...$types);
		}

		return null;
	}

	private function findParentClassNameType(string $className): Type
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return new UnionType([
				new ClassStringType(),
				new ConstantBooleanType(false),
			]);
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if ($classReflection->isInterface()) {
			return new UnionType([
				new ClassStringType(),
				new ConstantBooleanType(false),
			]);
		}

		return $this->findParentClassType($classReflection);
	}

	private function findParentClassType(
		ClassReflection $classReflection,
	): Type
	{
		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return new ConstantBooleanType(false);
		}

		return new ConstantStringType($parentClass->getName(), true);
	}

}
