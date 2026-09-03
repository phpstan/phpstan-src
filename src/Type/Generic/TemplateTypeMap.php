<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
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
 * - **types** (upper bounds): The concrete type inferred or declared for each template.
 *   For `@template T of Countable`, if T is inferred as `array`, types maps T → array.
 * - **lowerBoundTypes**: Types inferred from contravariant positions (e.g. parameter types).
 *   Used during type inference to narrow template types from below.
 *
 * TemplateTypeMap supports set operations (union, intersect, benevolentUnion) that combine
 * maps from different code paths, and resolveToBounds() which replaces unresolved template
 * types with their declared bounds.
 *
 * Common usage: ParametersAcceptor::getTemplateTypeMap() returns the template declarations,
 * and ParametersAcceptor::getResolvedTemplateTypeMap() returns inferred concrete types.
 * Type::inferTemplateTypes() produces a TemplateTypeMap from a concrete type.
 *
 * @api
 */
final class TemplateTypeMap
{

	private static ?TemplateTypeMap $empty = null;

	private ?TemplateTypeMap $resolvedToBounds = null;

	/**
	 * @api
	 * @param array<string, Type> $types Concrete types for each template parameter (upper bounds)
	 * @param array<string, Type> $lowerBoundTypes Types inferred from contravariant positions
	 */
	public function __construct(private array $types, private array $lowerBoundTypes = [])
	{
	}

	public function convertToLowerBoundTypes(): self
	{
		$lowerBoundTypes = $this->types;
		foreach ($this->lowerBoundTypes as $name => $type) {
			if (isset($lowerBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($lowerBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					continue;
				}
				$lowerBoundTypes[$name] = $intersection;
			} else {
				$lowerBoundTypes[$name] = $type;
			}
		}

		return new self([], $lowerBoundTypes);
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
		return count($this->types + $this->lowerBoundTypes);
	}

	/** @return array<string, Type> */
	public function getTypes(): array
	{
		$types = $this->types;
		foreach ($this->lowerBoundTypes as $name => $type) {
			if (array_key_exists($name, $types)) {
				continue;
			}

			$types[$name] = $type;
		}

		return $types;
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
		$lowerBoundTypes = $this->lowerBoundTypes;

		unset($types[$name]);
		unset($lowerBoundTypes[$name]);

		if (count($types) === 0 && count($lowerBoundTypes) === 0) {
			return self::createEmpty();
		}

		return new self($types, $lowerBoundTypes);
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

		$resultLowerBoundTypes = $this->lowerBoundTypes;
		foreach ($other->lowerBoundTypes as $name => $type) {
			if (isset($resultLowerBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($resultLowerBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					continue;
				}
				$resultLowerBoundTypes[$name] = $intersection;
			} else {
				$resultLowerBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultLowerBoundTypes);
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

		$resultLowerBoundTypes = $this->lowerBoundTypes;
		foreach ($other->lowerBoundTypes as $name => $type) {
			if (isset($resultLowerBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($resultLowerBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					continue;
				}
				$resultLowerBoundTypes[$name] = $intersection;
			} else {
				$resultLowerBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultLowerBoundTypes);
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

		$resultLowerBoundTypes = $this->lowerBoundTypes;
		foreach ($other->lowerBoundTypes as $name => $type) {
			if (isset($resultLowerBoundTypes[$name])) {
				$resultLowerBoundTypes[$name] = TypeCombinator::union($resultLowerBoundTypes[$name], $type);
			} else {
				$resultLowerBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultLowerBoundTypes);
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
		return $this->resolvedToBounds = $this->map(static fn (string $name, Type $type): Type => TypeTraverser::map(
			$type,
			static fn (Type $type, callable $traverse): Type => $type instanceof TemplateType ? $traverse($type->getDefault() ?? $type->getBound()) : $traverse($type),
		));
	}

}
