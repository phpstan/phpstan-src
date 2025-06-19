<?php declare(strict_types = 1);

namespace PHPStan\Type\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionClass;
use function count;
use function sort;

#[AutowiredService]
final class ClassNameUsageLocationCreateIdentifierDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return ClassNameUsageLocation::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createIdentifier';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$secondPartType = $scope->getType($args[0]->value);
		$secondPartValues = $secondPartType->getConstantStrings();
		if (count($secondPartValues) === 0) {
			return null;
		}

		$reflection = new ReflectionClass(ClassNameUsageLocation::class);
		$identifiers = [];
		$locationValueType = $scope->getType(new PropertyFetch($methodCall->var, 'value'));
		foreach ($reflection->getConstants() as $constant) {
			if (!$locationValueType->isSuperTypeOf($scope->getTypeFromValue($constant))->yes()) {
				continue;
			}
			$location = ClassNameUsageLocation::from($constant);
			foreach ($secondPartValues as $secondPart) {
				$identifiers[] = $location->createIdentifier($secondPart->getValue());
			}
		}

		sort($identifiers);

		$types = [];
		foreach ($identifiers as $identifier) {
			$types[] = $scope->getTypeFromValue($identifier);
		}

		return TypeCombinator::union(...$types);
	}

}
