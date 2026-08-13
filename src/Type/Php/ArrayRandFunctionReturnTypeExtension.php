<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Traverser\UnsafeArrayStringKeyCastingTraverser;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function is_int;

#[AutowiredService]
final class ArrayRandFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/**
	 * Above this many picked keys the shape stops being worth its cost, and
	 * ConstantArrayTypeBuilder would degrade it to a general array anyway.
	 */
	private const KEY_COUNT_LIMIT = 100;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_rand';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		$argsCount = count($args);
		if ($argsCount < 1) {
			return null;
		}

		$firstArgType = $scope->getType($args[0]->value);
		// The picked keys come back as values of their own, so PHP's array key
		// cast applies to them.
		$keyType = UnsafeArrayStringKeyCastingTraverser::castReadKeyType($firstArgType->getIterableKeyType());

		if ($argsCount < 2) {
			return $keyType;
		}

		$secondArgType = $scope->getType($args[1]->value);

		$one = new ConstantIntegerType(1);
		if ($one->isSuperTypeOf($secondArgType)->yes()) {
			return $keyType;
		}

		$pickedKeys = $this->pickedKeysType($keyType, $secondArgType);
		if (IntegerRangeType::fromInterval(2, null)->isSuperTypeOf($secondArgType)->yes()) {
			return $pickedKeys;
		}

		return TypeCombinator::union($keyType, $pickedKeys);
	}

	/**
	 * array_rand() picks $num distinct keys and hands them back in the array's
	 * own order, so a known $num gives an exact tuple. Returning an array at all
	 * means $num was at least 2 - one key comes back on its own.
	 */
	private function pickedKeysType(Type $keyType, Type $numType): Type
	{
		$constantNums = $numType->getConstantScalarValues();
		if (
			count($constantNums) === 1
			&& is_int($constantNums[0])
			&& $constantNums[0] >= 2
			&& $constantNums[0] <= self::KEY_COUNT_LIMIT
		) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			for ($i = 0; $i < $constantNums[0]; $i++) {
				$builder->setOffsetValueType(new ConstantIntegerType($i), $keyType);
			}

			return $builder->getArray();
		}

		return TypeCombinator::intersect(
			new ArrayType(new IntegerType(), $keyType),
			new AccessoryArrayListType(),
			new NonEmptyArrayType(),
		);
	}

}
