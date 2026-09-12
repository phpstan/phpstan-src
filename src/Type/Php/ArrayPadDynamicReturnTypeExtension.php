<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function abs;
use function count;

#[AutowiredService]
final class ArrayPadDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** How many of the guaranteed offsets are described with an accessory type. */
	private const GUARANTEED_OFFSETS_LIMIT = 8;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_pad';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[2])) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		$itemType = $scope->getType($args[2]->value);
		$valueType = TypeCombinator::union($arrayType->getIterableValueType(), $itemType);

		$returnType = new ArrayType(
			TypeCombinator::union($arrayType->getIterableKeyType(), new IntegerType()),
			$valueType,
		);

		$lengthType = $scope->getType($args[1]->value);
		if (
			$arrayType->isIterableAtLeastOnce()->yes()
			|| $lengthType->isSuperTypeOf(new ConstantIntegerType(0))->no()
		) {
			$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
		}

		if (!$arrayType->isList()->yes()) {
			return $returnType;
		}

		$returnType = TypeCombinator::intersect($returnType, new AccessoryArrayListType());

		// padding a list produces a list of at least abs($length) items, so that
		// many offsets are known to be there no matter how long the input was
		$lengthValues = $lengthType->getConstantScalarValues();
		if (count($lengthValues) !== 1 || !$lengthType->isInteger()->yes()) {
			return $returnType;
		}

		$guaranteedCount = abs((int) $lengthValues[0]);
		if ($guaranteedCount > self::GUARANTEED_OFFSETS_LIMIT) {
			$guaranteedCount = self::GUARANTEED_OFFSETS_LIMIT;
		}

		for ($offset = 0; $offset < $guaranteedCount; $offset++) {
			$returnType = TypeCombinator::intersect($returnType, new HasOffsetValueType(new ConstantIntegerType($offset), $valueType));
		}

		return $returnType;
	}

}
