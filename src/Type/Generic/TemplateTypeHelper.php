<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

class TemplateTypeHelper
{

	/**
	 * Replaces template types with standin types
	 */
	public static function resolveTemplateTypes(Type $type, TemplateTypeMap $standins, bool $keepErrorTypes = false): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($standins, $keepErrorTypes): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $standins->getType($type->getName());
				if ($newType === null) {
					return $traverse($type);
				}

				if ($newType instanceof ErrorType && !$keepErrorTypes) {
					return $traverse($type->getBound());
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

}
