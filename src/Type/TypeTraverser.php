<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;
use function spl_object_id;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/TypeTraverser.cpp')]
final class TypeTraverser
{

	/** @var callable(Type $type, callable(Type): Type $traverse): Type */
	private $cb;

	/**
	 * Results of mapMemoized() keyed by spl_object_id() of the mapped type,
	 * null in map(). The mapped type is kept alive next to its result so that
	 * its id cannot be reused by another object during the traversal.
	 *
	 * @var array<int, array{Type, Type}>|null
	 */
	private ?array $memo = null;

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
	 * Like map(), but the callback is called only once for each Type instance:
	 * a Type instance occurring repeatedly in the traversed type (e.g. a type
	 * alias used in many offsets of an array shape) is replaced with the result
	 * of its first occurrence, without traversing it again.
	 *
	 * Only for callbacks whose result and side effects do not depend on
	 * where in the traversed type, or how many times, the type occurs.
	 *
	 * @param TypeTraverserCallable|callable(Type $type, callable(Type): Type $traverse): Type $cb
	 */
	public static function mapMemoized(Type $type, TypeTraverserCallable|callable $cb): Type
	{
		$self = new self($cb);
		$self->memo = [];

		$traverser = $self->mapInternal($type);
		$self->memo = null;
		return $traverser;
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
		if ($this->memo === null) {
			return ($this->cb)($type, [$this, 'traverseInternal']);
		}

		$id = spl_object_id($type);
		if (isset($this->memo[$id])) {
			return $this->memo[$id][1];
		}

		$result = ($this->cb)($type, [$this, 'traverseInternal']);
		$this->memo[$id] = [$type, $result];

		return $result;
	}

	/** @internal */
	public function traverseInternal(Type $type): Type
	{
		return $type->traverse([$this, 'mapInternal']);
	}

}
