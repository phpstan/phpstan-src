<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Analyser\ThrowPoint;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class TooWideThrowTypeCheck
{

	/**
	 * @param Type $throwType
	 * @param ThrowPoint[] $throwPoints
	 * @return string[]
	 */
	public function check(Type $throwType, array $throwPoints): array
	{
		if ($throwType instanceof VoidType) {
			return [];
		}

		$throwPointType = TypeCombinator::union(...array_map(static function (ThrowPoint $throwPoint): Type {
			if (!$throwPoint->isExplicit()) {
				return new NeverType();
			}

			$type = $throwPoint->getType();
			if ($type->isSuperTypeOf(new ObjectType(\Throwable::class))->yes()) {
				return new NeverType();
			}

			return $type;
		}, $throwPoints));

		$throwClasses = [];
		foreach (TypeUtils::flattenTypes($throwType) as $type) {
			if (!$throwPointType instanceof NeverType && !$type->isSuperTypeOf($throwPointType)->no()) {
				continue;
			}

			$throwClasses[] = $type->describe(VerbosityLevel::typeOnly());
		}

		return $throwClasses;
	}

}
