<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ErrorType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VarianceAwareTypeTraverser;

class TemplateTypeHelper
{

	/**
	 * Replaces template types with standin types
	 */
	public static function resolveTemplateTypes(
		Type $type,
		TemplateTypeMap $standins,
		TemplateTypeVarianceMap $callSiteVariances,
		TemplateTypeVariance $positionVariance,
		bool $keepErrorTypes = false,
	): Type
	{
		return VarianceAwareTypeTraverser::map($type, $positionVariance, static function (Type $type, TemplateTypeVariance $variance, callable $traverse) use ($standins, $callSiteVariances, $keepErrorTypes): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $standins->getType($type->getName());
				if ($newType === null) {
					return $traverse($type, $variance);
				}

				if ($newType instanceof ErrorType && !$keepErrorTypes) {
					return $traverse($type->getBound(), $variance);
				}

				$callSiteVariance = $callSiteVariances->getVariance($type->getName());
				if ($callSiteVariance === null || $callSiteVariance->invariant()) {
					return $newType;
				}

				if (!$callSiteVariance->covariant() && $variance->covariant()) {
					return $traverse($type->getBound(), $variance);
				}

				if (!$callSiteVariance->contravariant() && $variance->contravariant()) {
					return new NonAcceptingNeverType();
				}

				return $newType;
			}

			return $traverse($type, $variance);
		});
	}

	public static function resolveToBounds(Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof TemplateType) {
				return $traverse($type->getBound());
			}

			return $traverse($type);
		});
	}

	/**
	 * @template T of Type
	 * @param T $type
	 * @return T
	 */
	public static function toArgument(Type $type): Type
	{
		/** @var T */
		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof TemplateType) {
				return $traverse($type->toArgument());
			}

			return $traverse($type);
		});
	}

}
