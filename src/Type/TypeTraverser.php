<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TraversableWithVariance;

final class TypeTraverser
{

	/** @var callable(Type $type, callable(Type): Type $traverse): Type */
	private $cb;

	/**
	 * Map a Type recursively
	 *
	 * For every Type instance, the callback can return a new Type, and/or
	 * decide to traverse inner types or to ignore them.
	 *
	 * The following example converts constant strings to objects, while
	 * preserving unions and intersections:
	 *
	 * TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
	 *     if ($type instanceof UnionType || $type instanceof IntersectionType) {
	 *         // Traverse inner types
	 *         return $traverse($type);
	 *     }
	 *     if ($type instanceof ConstantStringType) {
	 *         // Replaces the current type, and don't traverse
	 *         return new ObjectType($type->getValue());
	 *     }
	 *     // Replaces the current type, and don't traverse
	 *     return new MixedType();
	 * });
	 *
	 * @api
	 * @param TypeTraverserCallable|callable(Type $type, callable(Type): Type $traverse): Type $cb
	 */
	public static function map(Type $type, TypeTraverserCallable|callable $cb): Type
	{
		$self = new self($cb);

		return $self->mapInternal($type);
	}

	/**
	 * map() that tells the callback the variance the type stands in - the variance
	 * Type::getReferencedTemplateTypes() reports for a template type there: reversed for the
	 * parameters of a callable, the declared or projected one for the arguments of a generic
	 * object (see TraversableWithVariance), unchanged for the parts of everything else.
	 * A template type referenced in several positions of one type is a single object, so
	 * only the traversal can tell its occurrences apart.
	 *
	 * @param callable(Type $type, TemplateTypeVariance $positionVariance, callable(Type): Type $traverse): Type $cb
	 */
	public static function mapWithVariance(Type $type, TemplateTypeVariance $positionVariance, callable $cb): Type
	{
		return $cb($type, $positionVariance, static function (Type $type) use ($positionVariance, $cb): Type {
			if ($type instanceof TraversableWithVariance) {
				return $type->traverseWithVariance(
					$positionVariance,
					static fn (Type $part, TemplateTypeVariance $partVariance): Type => self::mapWithVariance($part, $partVariance, $cb),
				);
			}

			return $type->traverse(static fn (Type $part): Type => self::mapWithVariance($part, $positionVariance, $cb));
		});
	}

	/** @param TypeTraverserCallable|callable(Type $type, callable(Type): Type $traverse): Type $cb */
	private function __construct(TypeTraverserCallable|callable $cb)
	{
		if ($cb instanceof TypeTraverserCallable) {
			$this->cb = static fn (Type $type, callable $traverse): Type => $cb->traverse($type, $traverse);
		} else {
			$this->cb = $cb;
		}
	}

	/** @internal */
	public function mapInternal(Type $type): Type
	{
		return ($this->cb)($type, [$this, 'traverseInternal']);
	}

	/** @internal */
	public function traverseInternal(Type $type): Type
	{
		return $type->traverse([$this, 'mapInternal']);
	}

}
