<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function get_class;

final class TemplateTypeFactory
{

	public static function create(TemplateTypeScope $scope, string $name, ?Type $bound, TemplateTypeVariance $variance): TemplateType
	{
		$strategy = new TemplateTypeParameterStrategy();

		if ($bound === null) {
			return new TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(true));
		}

		$boundClass = get_class($bound);
		if ($bound instanceof ObjectType && ($boundClass === ObjectType::class || $bound instanceof TemplateType)) {
			return new TemplateObjectType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof GenericObjectType && ($boundClass === GenericObjectType::class || $bound instanceof TemplateType)) {
			return new TemplateGenericObjectType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof ObjectWithoutClassType && ($boundClass === ObjectWithoutClassType::class || $bound instanceof TemplateType)) {
			return new TemplateObjectWithoutClassType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof ArrayType && ($boundClass === ArrayType::class || $bound instanceof TemplateType)) {
			return new TemplateArrayType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof ConstantArrayType && ($boundClass === ConstantArrayType::class || $bound instanceof TemplateType)) {
			return new TemplateConstantArrayType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof StringType && ($boundClass === StringType::class || $bound instanceof TemplateType)) {
			return new TemplateStringType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof IntegerType && ($boundClass === IntegerType::class || $bound instanceof TemplateType)) {
			return new TemplateIntegerType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof FloatType && ($boundClass === FloatType::class || $bound instanceof TemplateType)) {
			return new TemplateFloatType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof BooleanType && ($boundClass === BooleanType::class || $bound instanceof TemplateType)) {
			return new TemplateBooleanType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof MixedType && ($boundClass === MixedType::class || $bound instanceof TemplateType)) {
			return new TemplateMixedType($scope, $strategy, $variance, $name, $bound);
		}

		if ($bound instanceof UnionType) {
			if ($boundClass === UnionType::class || $bound instanceof TemplateUnionType) {
				return new TemplateUnionType($scope, $strategy, $variance, $name, $bound);
			}

			if ($bound instanceof BenevolentUnionType) {
				return new TemplateBenevolentUnionType($scope, $strategy, $variance, $name, $bound);
			}
		}

		return new TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(true));
	}

	public static function fromTemplateTag(TemplateTypeScope $scope, TemplateTag $tag): TemplateType
	{
		return self::create($scope, $tag->getName(), $tag->getBound(), $tag->getVariance());
	}

}
