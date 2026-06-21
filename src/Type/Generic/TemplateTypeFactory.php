<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\KeyOfType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function get_class;

final class TemplateTypeFactory
{

	/**
	 * @param non-empty-string $name
	 */
	public static function create(TemplateTypeScope $scope, string $name, ?Type $bound, TemplateTypeVariance $variance, ?TemplateTypeStrategy $strategy = null, ?Type $default = null): TemplateType
	{
		$strategy ??= new TemplateTypeParameterStrategy();

		if ($bound === null) {
			return new TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(true), $default);
		}

		$boundClass = get_class($bound);
		if ($bound instanceof GenericObjectType && ($boundClass === GenericObjectType::class || $bound instanceof TemplateType)) {
			return new TemplateGenericObjectType($scope, $strategy, $variance, $name, $bound, $default);
		}

		// Catches plain ObjectType and any other object subtype without a dedicated
		// Template* class (e.g. enum-case object types), preserving the precise bound
		// instead of widening it to TemplateMixedType.
		if ($bound instanceof ObjectType) {
			return new TemplateObjectType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof ObjectWithoutClassType && ($boundClass === ObjectWithoutClassType::class || $bound instanceof TemplateType)) {
			return new TemplateObjectWithoutClassType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof ArrayType && ($boundClass === ArrayType::class || $bound instanceof TemplateType)) {
			return new TemplateArrayType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof ConstantArrayType && ($boundClass === ConstantArrayType::class || $bound instanceof TemplateType)) {
			return new TemplateConstantArrayType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof ObjectShapeType && ($boundClass === ObjectShapeType::class || $bound instanceof TemplateType)) {
			return new TemplateObjectShapeType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof StringType && ($boundClass === StringType::class || $bound instanceof TemplateType)) {
			return new TemplateStringType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof ConstantStringType && ($boundClass === ConstantStringType::class || $bound instanceof TemplateType)) {
			return new TemplateConstantStringType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof IntegerType && ($boundClass === IntegerType::class || $bound instanceof IntegerRangeType || $bound instanceof TemplateType)) {
			return new TemplateIntegerType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof ConstantIntegerType && ($boundClass === ConstantIntegerType::class || $bound instanceof TemplateType)) {
			return new TemplateConstantIntegerType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof FloatType && ($boundClass === FloatType::class || $bound instanceof ConstantFloatType || $bound instanceof TemplateType)) {
			return new TemplateFloatType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof BooleanType && ($boundClass === BooleanType::class || $bound->isTrue()->yes() || $bound->isFalse()->yes() || $bound instanceof TemplateType)) {
			return new TemplateBooleanType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof MixedType && ($boundClass === MixedType::class || $bound instanceof TemplateType)) {
			return new TemplateMixedType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof UnionType) {
			if ($boundClass === UnionType::class || $bound instanceof TemplateUnionType) {
				return new TemplateUnionType($scope, $strategy, $variance, $name, $bound, $default);
			}

			if ($bound instanceof BenevolentUnionType) {
				return new TemplateBenevolentUnionType($scope, $strategy, $variance, $name, $bound, $default);
			}
		}

		if ($bound instanceof IntersectionType) {
			return new TemplateIntersectionType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof KeyOfType && ($boundClass === KeyOfType::class || $bound instanceof TemplateType)) {
			return new TemplateKeyOfType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof IterableType && ($boundClass === IterableType::class || $bound instanceof TemplateType)) {
			return new TemplateIterableType($scope, $strategy, $variance, $name, $bound, $default);
		}

		if ($bound instanceof NullType && ($boundClass === NullType::class || $bound instanceof TemplateType)) {
			return new TemplateNullType($scope, $strategy, $variance, $name, $bound, $default);
		}

		return new TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(true), $default);
	}

	public static function fromTemplateTag(TemplateTypeScope $scope, TemplateTag $tag): TemplateType
	{
		return self::create($scope, $tag->getName(), $tag->getBound(), $tag->getVariance(), default: $tag->getDefault());
	}

}
