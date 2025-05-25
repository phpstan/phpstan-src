<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function count;
use function in_array;
use function strtolower;

#[AutowiredService]
final class ThrowableReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Throwable::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getCode';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$type = $scope->getType($methodCall->var);
		$types = [];
		$pdoException = new ObjectType('PDOException');
		foreach ($type->getObjectClassNames() as $class) {
			$classType = new ObjectType($class);
			if ($classType->getClassReflection() !== null) {
				$classReflection = $classType->getClassReflection();
				foreach ($classReflection->getMethodTags() as $methodName => $methodTag) {
					if (strtolower($methodName) !== 'getcode') {
						continue;
					}

					$types[] = $methodTag->getReturnType();
					continue 2;
				}
			}

			if ($pdoException->isSuperTypeOf($classType)->yes()) {
				$types[] = new BenevolentUnionType([new IntegerType(), new StringType()]);
				continue;
			}

			if (in_array(strtolower($class), [
				'throwable',
				'exception',
				'runtimeexception',
			], true)) {
				$types[] = new BenevolentUnionType([new IntegerType(), new StringType()]);
				continue;
			}

			$types[] = new IntegerType();
		}

		if (count($types) === 0) {
			return new ErrorType();
		}

		return TypeCombinator::union(...$types);
	}

}
