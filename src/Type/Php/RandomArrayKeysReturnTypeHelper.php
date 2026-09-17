<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * Describes the keys picked out of an array at random, as done by array_rand()
 * and Random\Randomizer::pickArrayKeys().
 */
#[AutowiredService]
final class RandomArrayKeysReturnTypeHelper
{

	public function getPickedKeyType(Type $arrayType): Type
	{
		$arrayKeyType = new UnionType([new IntegerType(), new StringType()]);
		if ($arrayType->isIterableAtLeastOnce()->no()) {
			// Picking out of an empty array always throws, there's no key to describe.
			return $arrayKeyType;
		}

		return TypeCombinator::intersect($arrayType->getIterableKeyType(), $arrayKeyType);
	}

	/**
	 * Picking more than one key returns them re-indexed from zero, keeping their original order.
	 */
	public function getPickedKeysListType(Type $arrayType): Type
	{
		return TypeCombinator::intersect(
			new ArrayType(new IntegerType(), $this->getPickedKeyType($arrayType)),
			new AccessoryArrayListType(),
			new NonEmptyArrayType(),
		);
	}

}
