<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function count;

/**
 * Maps template type parameter names to their resolved types.
 *
 * This is the core data structure for PHPStan's generics support. When a class declares
 * `@template T`, `@template U of object`, etc., the TemplateTypeMap tracks what concrete
 * types T and U resolve to in a particular context.
 *
 * Two kinds of type bindings are tracked:
 * - **types**: The concrete type inferred or declared for each template. During inference
 *   these are lower bounds: `T[]` receiving `int[]` means T :> int.
 * - **upperBoundTypes**: Types inferred from contravariant positions, such as the parameter
 *   of `callable(T): void` receiving `callable(int): void`, which means T <: int.
 *   A template is resolved to its lower bound when it has one, otherwise to its upper bound.
 *
 * TemplateTypeMap supports set operations (union, intersect, benevolentUnion) that combine
 * maps from different code paths: union() joins the lower bounds and meets the upper
 * bounds, intersect() the other way around. resolveToBounds() replaces unresolved template
 * types with their declared bounds.
 *
 * Common usage: ParametersAcceptor::getTemplateTypeMap() returns the template declarations,
 * and ParametersAcceptor::getResolvedTemplateTypeMap() returns inferred concrete types.
 * Type::inferTemplateTypes() produces a TemplateTypeMap from a concrete type.
 *
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TemplateTypeMap.cpp')]
final class TemplateTypeMap
{

	private static ?TemplateTypeMap $empty = null;

	private ?TemplateTypeMap $resolvedToBounds = null;

	/** @var array<string, Type> */
	private array $upperBoundTypes;

	/**
	 * @api
	 * @param array<string, Type> $types Concrete types for each template parameter, lower bounds during inference
	 * @param array<string, Type> $lowerBoundTypes Upper bounds, inferred from contravariant positions (the name is kept for backward compatibility)
	 */
	public function __construct(private array $types, array $lowerBoundTypes = [])
	{
		$this->upperBoundTypes = $lowerBoundTypes;
	}

	/**
	 * The map inferred from a type in a contravariant position, such as the parameter of a
	 * callable: its lower bounds become upper bounds and vice versa.
	 */
	public function swapBounds(): self
	{
		return new self($this->upperBoundTypes, $this->types);
	}

	/** @deprecated Use swapBounds() */
	public function convertToLowerBoundTypes(): self
	{
		return $this->swapBounds();
	}

	public static function createEmpty(): self
	{
		$empty = self::$empty;

		if ($empty !== null) {
			return $empty;
		}

		$empty = new self([], []);
		self::$empty = $empty;

		return $empty;
	}

	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	public function count(): int
	{
		return count($this->types + $this->upperBoundTypes);
	}

	/** @return array<string, Type> */
	public function getTypes(): array
	{
		$types = $this->types;
		foreach ($this->upperBoundTypes as $name => $type) {
			if (array_key_exists($name, $types)) {
				continue;
			}

			$types[$name] = $type;
		}

		return $types;
	}

	/**
	 * The upper bounds alone, including those of templates that also have a lower bound,
	 * which getTypes() prefers.
	 *
	 * @return array<string, Type>
	 */
	public function getUpperBoundTypes(): array
	{
		return $this->upperBoundTypes;
	}

	public function hasType(string $name): bool
	{
		return array_key_exists($name, $this->getTypes());
	}

	public function getType(string $name): ?Type
	{
		return $this->getTypes()[$name] ?? null;
	}

	public function unsetType(string $name): self
	{
		if (!$this->hasType($name)) {
			return $this;
		}

		$types = $this->types;
		$upperBoundTypes = $this->upperBoundTypes;

		unset($types[$name]);
		unset($upperBoundTypes[$name]);

		if (count($types) === 0 && count($upperBoundTypes) === 0) {
			return self::createEmpty();
		}

		return new self($types, $upperBoundTypes);
	}

	public function union(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = self::combine($result[$name], $type, static fn (Type $a, Type $b): Type => TypeCombinator::union($a, $b));
			} else {
				$result[$name] = $type;
			}
		}

		$resultUpperBoundTypes = $this->upperBoundTypes;
		foreach ($other->upperBoundTypes as $name => $type) {
			if (isset($resultUpperBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($resultUpperBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					// conflicting upper bounds: the first one wins, so that the argument
					// checked against it reports the conflict in terms of real types
					continue;
				}
				$resultUpperBoundTypes[$name] = $intersection;
			} else {
				$resultUpperBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultUpperBoundTypes);
	}

	public function benevolentUnion(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = self::combine($result[$name], $type, static fn (Type $a, Type $b): Type => TypeUtils::toBenevolentUnion(TypeCombinator::union($a, $b)));
			} else {
				$result[$name] = $type;
			}
		}

		$resultUpperBoundTypes = $this->upperBoundTypes;
		foreach ($other->upperBoundTypes as $name => $type) {
			if (isset($resultUpperBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($resultUpperBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					// conflicting upper bounds: the first one wins, so that the argument
					// checked against it reports the conflict in terms of real types
					continue;
				}
				$resultUpperBoundTypes[$name] = $intersection;
			} else {
				$resultUpperBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultUpperBoundTypes);
	}

	/**
	 * A template left unresolved on purpose by one occurrence of the parameter type carries
	 * no type at all, so any type another occurrence did infer wins over it outright.
	 *
	 * @param callable(Type, Type): Type $cb
	 */
	private static function combine(Type $a, Type $b, callable $cb): Type
	{
		if ($a instanceof AbsorbedTemplateArgumentType) {
			return $b;
		}
		if ($b instanceof AbsorbedTemplateArgumentType) {
			return $a;
		}

		return $cb($a, $b);
	}

	public function intersect(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeCombinator::intersect($result[$name], $type);
			} else {
				$result[$name] = $type;
			}
		}

		$resultUpperBoundTypes = $this->upperBoundTypes;
		foreach ($other->upperBoundTypes as $name => $type) {
			if (isset($resultUpperBoundTypes[$name])) {
				$resultUpperBoundTypes[$name] = TypeCombinator::union($resultUpperBoundTypes[$name], $type);
			} else {
				$resultUpperBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultUpperBoundTypes);
	}

	/** @param callable(string,Type):Type $cb */
	public function map(callable $cb): self
	{
		$types = [];
		foreach ($this->getTypes() as $name => $type) {
			$types[$name] = $cb($name, $type);
		}

		return new self($types);
	}

	/**
	 * Replaces unresolved TemplateType values with their declared bounds (or defaults).
	 */
	public function resolveToBounds(): self
	{
		if ($this->resolvedToBounds !== null) {
			return $this->resolvedToBounds;
		}
		return $this->resolvedToBounds = $this->map(static fn (string $name, Type $type): Type => TemplateTypeHelper::resolveToDefaults($type));
	}

}
