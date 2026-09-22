<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Error;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use ValueError;
use function count;
use const PHP_INT_MAX;

/**
 * array_fill() throws ValueError for a $count outside 0 and INT_MAX, and
 * Error when the keys would pass PHP_INT_MAX.
 */
#[AutowiredService]
final class ArrayFillFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	private const MAX_COUNT = 2147483647;

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_fill';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) < 3) {
			return $functionReflection->getThrowType();
		}

		$startType = $scope->getNativeType($args[0]->value);
		$countType = $scope->getNativeType($args[1]->value);

		$throwTypes = [];
		if (!IntegerRangeType::fromInterval(0, self::MAX_COUNT)->isSuperTypeOf($countType)->yes()) {
			$throwTypes[] = new ObjectType(ValueError::class);
		}

		// "Cannot add element to the array as the next element is already occupied"
		// for a start index from which the last key would be greater than PHP_INT_MAX
		if (
			!(new ConstantIntegerType(0))->isSuperTypeOf($countType)->yes()
			&& !IntegerRangeType::fromInterval(null, PHP_INT_MAX - self::MAX_COUNT + 1)->isSuperTypeOf($startType)->yes()
		) {
			$throwTypes[] = new ObjectType(Error::class);
		}

		if (count($throwTypes) === 0) {
			return new VoidType();
		}

		return TypeCombinator::union(...$throwTypes);
	}

}
