<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionType;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function in_array;

final class AdapterReflectionEnumCaseDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/**
	 * @param class-string $class
	 */
	public function __construct(private string $class)
	{
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'getDocComment',
			'getType',
		], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		// The PHP 7 Reflection API returns false where PHP 8 has narrower return types.
		if (!$scope->getPhpVersion()->hasPhp8ReflectionReturnTypes()->no()) {
			return null;
		}

		if ($methodReflection->getName() === 'getDocComment') {
			return new UnionType([
				new StringType(),
				new ConstantBooleanType(false),
			]);
		}

		if ($methodReflection->getName() === 'getType') {
			return new UnionType([
				new ObjectType(ReflectionType::class),
				new NullType(),
			]);
		}

		return null;
	}

}
