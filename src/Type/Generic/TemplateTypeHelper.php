<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_values;

final class TemplateTypeHelper
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
		if (!$type->hasTemplateOrLateResolvableType()) {
			return $type;
		}

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
					return $traverse($type->getDefault() ?? $type->getBound());
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

	public static function resolveToDefaults(Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof TemplateType) {
				return $traverse($type->getDefault() ?? $type->getBound());
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
	 * Widens `never` type arguments of generic objects to their template bounds.
	 *
	 * An object constructed empty - like `new ArrayObject()` - gets `never` type
	 * arguments inferred. Once the object might have been mutated, `never` no longer
	 * describes what it can contain, but a wider type inferred from the call site
	 * would be unsound, so the bounds are the safest thing to fall back to.
	 */
	public static function widenNeverTypeArguments(Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof GenericObjectType) {
				$widenedTypes = self::widenNeverTypesToBounds($type->getTypes(), $type->getClassReflection());
				if ($widenedTypes !== null) {
					return $traverse(new GenericObjectType(
						$type->getClassName(),
						$widenedTypes,
						$type->getSubtractedType(),
						variances: $type->getVariances(),
					));
				}
			} elseif ($type instanceof GenericStaticType) {
				$widenedTypes = self::widenNeverTypesToBounds($type->getTypes(), $type->getClassReflection());
				if ($widenedTypes !== null) {
					return $traverse(new GenericStaticType(
						$type->getClassReflection(),
						$widenedTypes,
						$type->getSubtractedType(),
						$type->getVariances(),
					));
				}
			}

			return $traverse($type);
		});
	}

	/**
	 * @param array<int, Type> $typeArguments
	 * @return array<int, Type>|null null when nothing was widened
	 */
	private static function widenNeverTypesToBounds(array $typeArguments, ?ClassReflection $classReflection): ?array
	{
		if ($classReflection === null) {
			return null;
		}

		$templateTypes = array_values($classReflection->getTemplateTypeMap()->getTypes());
		$widened = false;
		foreach ($typeArguments as $i => $typeArgument) {
			if (!$typeArgument instanceof NeverType) {
				continue;
			}
			if (!isset($templateTypes[$i])) {
				continue;
			}
			$templateType = $templateTypes[$i];
			if (!$templateType instanceof TemplateType) {
				continue;
			}

			$typeArguments[$i] = $templateType->getBound();
			$widened = true;
		}

		return $widened ? $typeArguments : null;
	}

	/**
	 * @template T of Type
	 * @param T $type
	 * @return T
	 */
	public static function toArgument(Type $type): Type
	{
		$ownedTemplates = [];

		/** @var T */
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$ownedTemplates): Type {
			if ($type instanceof ParametersAcceptor) {
				$templateTypeMap = $type->getTemplateTypeMap();

				foreach ($type->getParameters() as $parameter) {
					$parameterType = $parameter->getType();
					if (!($parameterType instanceof TemplateType) || !$templateTypeMap->hasType($parameterType->getName())) {
						continue;
					}

					$ownedTemplates[] = $parameterType;
				}

				$returnType = $type->getReturnType();

				if ($returnType instanceof TemplateType && $templateTypeMap->hasType($returnType->getName())) {
					$ownedTemplates[] = $returnType;
				}
			}

			foreach ($ownedTemplates as $ownedTemplate) {
				if ($ownedTemplate === $type) {
					return $traverse($type);
				}
			}

			if ($type instanceof TemplateType) {
				// templates declared by a callable<T>(...)/Closure<T>(...) type in the signature
				// belong to the callable value, not to the entered function
				if ($type->getScope()->equals(TemplateTypeScope::createWithAnonymousFunction())) {
					return $traverse($type);
				}

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
