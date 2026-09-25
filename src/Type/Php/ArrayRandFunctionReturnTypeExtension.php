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
use PHPStan\Type\StringType;
use PHPStan\Type\Traverser\UnsafeArrayStringKeyCastingTraverser;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
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
		$keyType = $this->getPickedKeyType($firstArgType);

		if ($argsCount < 2) {
			return $keyType;
		}

		$secondArgType = $scope->getType($args[1]->value);

		$one = new ConstantIntegerType(1);
		if ($one->isSuperTypeOf($secondArgType)->yes()) {
			return $keyType;
		}

		$keysListType = $this->getPickedKeysListType($firstArgType, $secondArgType);

		$bigger2 = IntegerRangeType::fromInterval(2, null);
		if ($bigger2->isSuperTypeOf($secondArgType)->yes()) {
			return $keysListType;
		}

		return TypeCombinator::union($keyType, $keysListType);
	}

	private function getPickedKeyType(Type $arrayType): Type
	{
		$arrayKeyType = new UnionType([new IntegerType(), new StringType()]);
		if ($arrayType->isIterableAtLeastOnce()->no()) {
			// Picking out of an empty array always throws, there's no key to describe.
			return $arrayKeyType;
		}

		// The picked keys come back as values of their own, so PHP's array key
		// cast applies to them.
		return UnsafeArrayStringKeyCastingTraverser::castReadKeyType(
			TypeCombinator::intersect($arrayType->getIterableKeyType(), $arrayKeyType),
		);
	}

	/**
	 * Picking more than one key returns them re-indexed from zero, keeping their
	 * original order, so a known $num gives an exact tuple.
	 */
	private function getPickedKeysListType(Type $arrayType, Type $numType): Type
	{
		$keyType = $this->getPickedKeyType($arrayType);

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
