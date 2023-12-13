<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;

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
		$references = $type->getReferencedTemplateTypes($positionVariance);

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($standins, $references, $callSiteVariances, $keepErrorTypes): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $standins->getType($type->getName());

				$variance = TemplateTypeVariance::createInvariant();
				foreach ($references as $reference) {
					// this uses identity to distinguish between different occurrences of the same template type
					// see https://github.com/phpstan/phpstan-src/pull/2485#discussion_r1328555397 for details
					if ($reference->getType() === $type) {
						$variance = $reference->getPositionVariance();
						break;
					}
				}

				if ($newType === null) {
					return $traverse($type);
				}

				if ($newType instanceof ErrorType && !$keepErrorTypes) {
					return $traverse($type->getBound());
				}

				$callSiteVariance = $callSiteVariances->getVariance($type->getName());
				if ($callSiteVariance === null || $callSiteVariance->invariant()) {
					return $newType;
				}

				if (!$callSiteVariance->covariant() && $variance->covariant()) {
					return $traverse($type->getBound());
				}

				if (!$callSiteVariance->contravariant() && $variance->contravariant()) {
					return new NonAcceptingNeverType();
				}

				return $newType;
			}

			return $traverse($type);
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

	public static function generalizeInferredTemplateType(TemplateType $templateType, Type $type): Type
	{
		if (!$templateType->getVariance()->covariant()) {
			$isArrayKey = $templateType->getBound()->describe(VerbosityLevel::precise()) === '(int|string)';
			if ($type->isScalar()->yes() && $isArrayKey) {
				$type = $type->generalize(GeneralizePrecision::templateArgument());
			} elseif ($type->isConstantValue()->yes() && (!$templateType->getBound()->isScalar()->yes() || $isArrayKey)) {
				$type = $type->generalize(GeneralizePrecision::templateArgument());
			}
		}

		return $type;
	}

}
