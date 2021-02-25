<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;

final class TemplateTypeFactory
{

	public static function create(TemplateTypeScope $scope, string $name, ?Type $bound, TemplateTypeVariance $variance): TemplateType
	{
		$strategy = new TemplateTypeParameterStrategy();

		if ($bound === null) {
			return new TemplateMixedType($scope, $strategy, $variance, $name);
		}

		$boundClass = get_class($bound);
		if ($bound instanceof TypeWithClassName && $boundClass === ObjectType::class) {
			return new TemplateObjectType($scope, $strategy, $variance, $name, $bound->getClassName());
		}
		if ($boundClass === ObjectWithoutClassType::class) {
			return new TemplateObjectWithoutClassType($scope, $strategy, $variance, $name);
		}

		if ($boundClass === StringType::class) {
			return new TemplateStringType($scope, $strategy, $variance, $name);
		}

		if ($boundClass === IntegerType::class) {
			return new TemplateIntegerType($scope, $strategy, $variance, $name);
		}

		if ($boundClass === MixedType::class) {
			return new TemplateMixedType($scope, $strategy, $variance, $name);
		}

		if ($bound instanceof UnionType) {
			if ($boundClass === UnionType::class) {
				return new TemplateUnionType($scope, $strategy, $variance, $bound->getTypes(), $name);
			}

			if ($boundClass === BenevolentUnionType::class) {
				return new TemplateBenevolentUnionType($scope, $strategy, $variance, $bound->getTypes(), $name);
			}
		}

		return new TemplateMixedType($scope, $strategy, $variance, $name);
	}

	public static function fromTemplateTag(TemplateTypeScope $scope, TemplateTag $tag): TemplateType
	{
		return self::create($scope, $tag->getName(), $tag->getBound(), $tag->getVariance());
	}

}
